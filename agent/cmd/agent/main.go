// Command agent is the backup agent.
//
// It runs on any Linux host, polls the master control plane over outbound
// HTTPS for due backup jobs, and drives a bundled kopia binary to snapshot
// files and databases into per-host repositories. No inbound ports are
// required on the protected host.
//
// Subcommands:
//
//	agent version
//	agent enroll -master URL -token TOKEN
//	agent run
package main

import (
	"context"
	"errors"
	"flag"
	"fmt"
	"os"
	"os/signal"
	"path/filepath"
	"runtime"
	"syscall"
	"time"

	"github.com/thelonelyfrog/backup/agent/internal/api"
	"github.com/thelonelyfrog/backup/agent/internal/config"
	"github.com/thelonelyfrog/backup/agent/internal/kopia"
	"github.com/thelonelyfrog/backup/agent/internal/runner"
	"github.com/thelonelyfrog/backup/agent/internal/selfupdate"
)

var version = "dev"

func main() {
	if len(os.Args) < 2 {
		os.Args = append(os.Args, "run")
	}
	cmd, args := os.Args[1], os.Args[2:]

	var err error
	switch cmd {
	case "version", "-v", "--version":
		err = cmdVersion(args)
	case "enroll":
		err = cmdEnroll(args)
	case "run":
		err = cmdRun(args)
	case "help", "-h", "--help":
		usage()
	default:
		usage()
		err = fmt.Errorf("unknown command %q", cmd)
	}
	if err != nil {
		fmt.Fprintln(os.Stderr, "error:", err)
		os.Exit(1)
	}
}

func usage() {
	fmt.Fprint(os.Stderr, `backup agent

usage:
  agent version
  agent enroll -master <url> -token <token>
  agent run
`)
}

func cmdVersion(_ []string) error {
	fmt.Printf("backup agent %s\n", version)
	bin, err := kopia.Locate("")
	if err != nil {
		fmt.Printf("kopia: not found (%v)\n", err)
		return nil
	}
	ctx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
	defer cancel()
	kv, _ := kopia.New(bin, "", "").Version(ctx)
	fmt.Printf("kopia: %s (%s)\n", kv, bin)
	return nil
}

func cmdEnroll(args []string) error {
	fs := flag.NewFlagSet("enroll", flag.ExitOnError)
	master := fs.String("master", "", "master control-plane base URL")
	token := fs.String("token", "", "one-time enrollment token")
	cfgPath := fs.String("config", config.DefaultPath(), "agent config path")
	fs.Parse(args)

	if *master == "" || *token == "" {
		return errors.New("both -master and -token are required")
	}
	hostname, _ := os.Hostname()
	ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
	defer cancel()

	resp, err := api.New(*master, "").Enroll(ctx, api.EnrollRequest{
		Token:        *token,
		Hostname:     hostname,
		OS:           runtime.GOOS,
		Arch:         runtime.GOARCH,
		AgentVersion: version,
	})
	if err != nil {
		return fmt.Errorf("enroll: %w", err)
	}

	cfg := config.Default()
	cfg.MasterURL = *master
	cfg.APIKey = resp.APIKey
	cfg.HostID = resp.HostID
	if err := cfg.Save(*cfgPath); err != nil {
		return err
	}
	fmt.Printf("enrolled as host %s; config saved to %s\n", resp.HostID, *cfgPath)
	return nil
}

func cmdRun(args []string) error {
	fs := flag.NewFlagSet("run", flag.ExitOnError)
	cfgPath := fs.String("config", config.DefaultPath(), "agent config path")
	once := fs.Bool("once", false, "poll once and exit (for testing)")
	fs.Parse(args)

	cfg, err := config.Load(*cfgPath)
	if errors.Is(err, config.ErrNotConfigured) || !cfg.Enrolled() {
		return fmt.Errorf("not enrolled: run `agent enroll` first (config: %s)", *cfgPath)
	}
	if err != nil {
		return err
	}

	ctx, stop := signal.NotifyContext(context.Background(), syscall.SIGINT, syscall.SIGTERM)
	defer stop()

	client := api.New(cfg.MasterURL, cfg.APIKey)
	kbin, err := kopia.Locate(cfg.KopiaPath)
	if err != nil {
		return err
	}
	workDir := filepath.Join(filepath.Dir(*cfgPath), "work")
	if err := os.MkdirAll(workDir, 0o700); err != nil {
		return err
	}

	interval := time.Duration(cfg.PollInterval)
	intervalChanged := false
	fmt.Printf("backup agent %s: polling %s every %s\n", version, cfg.MasterURL, interval)

	tick := func() {
		if hb, err := client.Heartbeat(ctx, version); err == nil {
			maybeSelfUpdate(ctx, hb.Update)
			// Adopt the master-configured poll cadence if it changed.
			if hb.PollIntervalSeconds > 0 {
				if d := time.Duration(hb.PollIntervalSeconds) * time.Second; d != interval {
					interval = d
					intervalChanged = true
				}
			}
		}
		job, err := client.Poll(ctx)
		if err != nil {
			fmt.Fprintln(os.Stderr, "poll:", err)
			return
		}
		if job != nil {
			fmt.Printf("run %s: job %s (%s)\n", job.RunID, job.JobID, job.Type)
			executeJob(ctx, client, kbin, workDir, job)
		}

		// Also pick up any queued restore for this host.
		rt, err := client.PollRestore(ctx)
		if err != nil {
			fmt.Fprintln(os.Stderr, "poll restore:", err)
			return
		}
		if rt != nil {
			fmt.Printf("restore %s: snapshot %s -> %s\n", rt.ID, rt.SnapshotID, rt.TargetPath)
			executeRestore(ctx, client, kbin, workDir, rt)
		}
	}

	tick()
	if *once {
		return nil
	}
	t := time.NewTicker(interval)
	defer t.Stop()
	for {
		select {
		case <-ctx.Done():
			fmt.Println("shutting down")
			return nil
		case <-t.C:
			tick()
			if intervalChanged {
				intervalChanged = false
				t.Reset(interval)
				fmt.Printf("poll interval updated to %s\n", interval)
			}
		}
	}
}

// maybeSelfUpdate installs a newer agent binary when the master advertises one
// (auto-update is gated server-side). On success it re-execs into the new build
// and never returns.
func maybeSelfUpdate(ctx context.Context, up *api.UpdateInfo) {
	if up == nil || up.Version == "" || up.URL == "" || up.Version == version {
		return
	}
	fmt.Printf("self-update: %s -> %s (%s)\n", version, up.Version, up.URL)
	if err := selfupdate.Apply(ctx, up.URL, up.Version); err != nil {
		fmt.Fprintln(os.Stderr, "self-update failed:", err)
		return
	}
	fmt.Printf("self-update: installed %s, restarting\n", up.Version)
	if err := selfupdate.Restart(); err != nil {
		fmt.Fprintln(os.Stderr, "self-update restart failed:", err)
	}
}

func executeJob(ctx context.Context, client *api.Client, kbin, workDir string, job *api.Job) {
	_ = client.Report(ctx, job.RunID, api.Report{Status: api.RunRunning})

	src, err := runner.BuildSource(job)
	if err != nil {
		_ = client.Report(ctx, job.RunID, api.Report{Status: api.RunFailed, Log: err.Error()})
		return
	}
	kclient := kopia.New(kbin,
		filepath.Join(workDir, "repo-"+job.JobID+".config"),
		filepath.Join(workDir, "cache-"+job.JobID),
	)
	ensure := runner.EnsureFromRepository(job.Repository)

	rep, err := runner.Execute(ctx, kclient, ensure, src, job.Repository.Password, runner.ExecOptions{
		Prune:       job.PruneAfterBackup,
		Verify:      job.VerifyAfterBackup,
		Maintenance: job.AutoMaintenance,
		Retention:   runner.RetentionFromJob(job.Retention),
		Excludes:    runner.ExcludesFor(job),
	})
	if err != nil {
		fmt.Fprintln(os.Stderr, "run failed:", err)
	} else {
		fmt.Printf("run %s: %s\n", job.RunID, rep.Log)
	}

	// Filesystem repos are written by root (the gateway); chown them to whoever
	// owns the parent directory so panels/file-managers can see the repo.
	if job.Repository.Backend == "filesystem" && job.Repository.FilesystemPath != "" {
		if e := chownToParentOwner(job.Repository.FilesystemPath); e != nil {
			fmt.Fprintln(os.Stderr, "chown repo:", e)
		}
	}

	_ = client.Report(ctx, job.RunID, rep)

	// Upload a file listing so the snapshot can be browsed in the UI.
	if rep.SnapshotID != "" {
		if files, err := kclient.ListFiles(ctx, rep.SnapshotID, job.Repository.Password, 5000); err == nil {
			_ = client.ReportIndex(ctx, job.RunID, files)
		}
	}
}

// chownToParentOwner recursively chowns path to the uid/gid that owns its
// parent directory. No-op (best effort) if not running as root.
func chownToParentOwner(path string) error {
	parent := filepath.Dir(path)
	fi, err := os.Stat(parent)
	if err != nil {
		return err
	}
	st, ok := fi.Sys().(*syscall.Stat_t)
	if !ok {
		return nil
	}
	uid, gid := int(st.Uid), int(st.Gid)
	return filepath.Walk(path, func(p string, _ os.FileInfo, err error) error {
		if err != nil {
			return err
		}
		return os.Chown(p, uid, gid)
	})
}

func executeRestore(ctx context.Context, client *api.Client, kbin, workDir string, rt *api.RestoreTask) {
	_ = client.ReportRestore(ctx, rt.ID, "running", "")

	kclient := kopia.New(kbin,
		filepath.Join(workDir, "restore-"+rt.ID+".config"),
		filepath.Join(workDir, "cache-restore-"+rt.ID),
	)
	if err := runner.EnsureFromRepository(rt.Repository)(ctx, kclient); err != nil {
		_ = client.ReportRestore(ctx, rt.ID, "failed", "repo: "+err.Error())
		return
	}
	if len(rt.Paths) > 0 {
		// Restore selected files/dirs, preserving their paths under the target.
		for _, p := range rt.Paths {
			dest := filepath.Join(rt.TargetPath, p)
			if err := os.MkdirAll(filepath.Dir(dest), 0o755); err != nil {
				_ = client.ReportRestore(ctx, rt.ID, "failed", "mkdir "+dest+": "+err.Error())
				return
			}
			if err := kclient.RestorePath(ctx, rt.SnapshotID, p, dest, rt.Repository.Password); err != nil {
				_ = client.ReportRestore(ctx, rt.ID, "failed", "restore "+p+": "+err.Error())
				return
			}
		}
		fmt.Printf("restore %s: %d path(s) -> %s\n", rt.ID, len(rt.Paths), rt.TargetPath)
		_ = client.ReportRestore(ctx, rt.ID, "success", fmt.Sprintf("restored %d path(s) to %s", len(rt.Paths), rt.TargetPath))
		return
	}

	if err := kclient.Restore(ctx, rt.SnapshotID, rt.TargetPath, rt.Repository.Password); err != nil {
		fmt.Fprintln(os.Stderr, "restore failed:", err)
		_ = client.ReportRestore(ctx, rt.ID, "failed", err.Error())
		return
	}
	fmt.Printf("restore %s: done -> %s\n", rt.ID, rt.TargetPath)
	_ = client.ReportRestore(ctx, rt.ID, "success", "restored to "+rt.TargetPath)
}
