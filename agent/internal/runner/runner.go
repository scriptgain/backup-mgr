// Package runner executes one backup job end to end on the agent.
//
// Flow: ensure the repository exists -> materialize the source into a local path
// -> kopia snapshot it -> return a Report for the master. Repository setup is
// injected as an EnsureFunc so production (S3) and tests (filesystem) share the
// same execution path.
package runner

import (
	"context"
	"encoding/json"
	"fmt"
	"os"

	"github.com/thelonelyfrog/backup/agent/internal/api"
	"github.com/thelonelyfrog/backup/agent/internal/kopia"
	"github.com/thelonelyfrog/backup/agent/internal/source"
)

// EnsureFunc connects/creates the repository for a run.
type EnsureFunc func(ctx context.Context, c *kopia.Client) error

// EnsureFromRepository picks the right backend (S3 or filesystem) for a run.
func EnsureFromRepository(repo api.Repository) EnsureFunc {
	if repo.Backend == "filesystem" {
		return func(ctx context.Context, c *kopia.Client) error {
			return c.EnsureFilesystem(ctx, kopia.FilesystemRepo{
				Path:     repo.FilesystemPath,
				Password: repo.Password,
			})
		}
	}
	return EnsureS3(repo)
}

// EnsureS3 builds an EnsureFunc for an S3-backed repository (production path).
func EnsureS3(repo api.Repository) EnsureFunc {
	return func(ctx context.Context, c *kopia.Client) error {
		return c.EnsureS3(ctx, kopia.S3Repo{
			Endpoint:    repo.S3Endpoint,
			Region:      repo.Region,
			Bucket:      repo.Bucket,
			Prefix:      repo.Prefix,
			AccessKeyID: repo.AccessKeyID,
			SecretKey:   repo.SecretAccessKey,
			Password:    repo.Password,
			Compression: repo.Compression,
		})
	}
}

// filesSource is the JSON shape for a files-connector source.
type filesSource struct {
	Root     string   `json:"root"`
	Excludes []string `json:"excludes"`
}

type diskImageSource struct {
	Devices []string `json:"devices"`
}

// ExcludesFor returns any exclude/ignore patterns for a job.
func ExcludesFor(job *api.Job) []string {
	switch job.Type {
	case "files":
		var s filesSource
		_ = json.Unmarshal(job.Source, &s)
		return s.Excludes
	case "composite":
		var s compositeSource
		_ = json.Unmarshal(job.Source, &s)
		return s.Excludes
	default:
		return nil
	}
}

// dbSource is the JSON shape for a database source.
type dbSource struct {
	Database string `json:"database"`
	User     string `json:"user"`
	Password string `json:"password"`
	Host     string `json:"host"`
	Port     string `json:"port"`
}

// compositeSource is the JSON shape for a composite "whole server" job: one or
// more file paths plus one or more database dumps, captured in one snapshot.
type compositeSource struct {
	Excludes []string `json:"excludes"`
	Paths    []struct {
		Label string `json:"label"`
		Root  string `json:"root"`
	} `json:"paths"`
	Databases []struct {
		Label    string `json:"label"`
		Engine   string `json:"engine"` // mysql | postgres
		Database string `json:"database"`
		User     string `json:"user"`
		Password string `json:"password"`
		Host     string `json:"host"`
		Port     string `json:"port"`
	} `json:"databases"`
}

// transportConfig is the connection detail for an agentless connector.
type transportConfig struct {
	Type       string `json:"type"`
	Host       string `json:"host"`
	Port       string `json:"port"`
	Username   string `json:"username"`
	Secret     string `json:"secret"`
	PrivateKey string `json:"private_key"`
}

// BuildSource constructs the Source for a job. Agentless connectors (ftp, ...)
// pull from a remote host using the job's transport; the agent connector reads
// local files or dumps a local database.
func BuildSource(job *api.Job) (source.Source, error) {
	connector := job.Connector
	if connector == "" {
		connector = "agent"
	}

	// Agentless connectors: pull from a remote host to a spool.
	if connector != "agent" {
		var tr transportConfig
		if len(job.Transport) > 0 {
			if err := json.Unmarshal(job.Transport, &tr); err != nil {
				return nil, fmt.Errorf("parse transport: %w", err)
			}
		}
		var s filesSource // "root" is the remote path; blank = whole account
		_ = json.Unmarshal(job.Source, &s)
		switch connector {
		case "ftp":
			return source.NewFTP(tr.Host, tr.Port, tr.Username, tr.Secret, s.Root), nil
		case "ssh", "sftp", "rsync":
			return source.NewRsyncSSH(tr.Host, tr.Port, tr.Username, tr.Secret, tr.PrivateKey, s.Root), nil
		default:
			return nil, fmt.Errorf("connector %q not implemented yet", connector)
		}
	}

	// Agent connector: local sources by data kind.
	switch job.Type {
	case "files":
		var s filesSource
		if err := json.Unmarshal(job.Source, &s); err != nil {
			return nil, fmt.Errorf("parse files source: %w", err)
		}
		return source.NewLocalFiles(s.Root), nil
	case "diskimage":
		var s diskImageSource
		if err := json.Unmarshal(job.Source, &s); err != nil {
			return nil, fmt.Errorf("parse diskimage source: %w", err)
		}
		return source.NewDiskImage(s.Devices), nil
	case "mysql":
		var s dbSource
		if err := json.Unmarshal(job.Source, &s); err != nil {
			return nil, fmt.Errorf("parse mysql source: %w", err)
		}
		return source.NewMySQLDump(s.Host, s.Port, s.User, s.Password, s.Database), nil
	case "postgres":
		var s dbSource
		if err := json.Unmarshal(job.Source, &s); err != nil {
			return nil, fmt.Errorf("parse postgres source: %w", err)
		}
		return source.NewPostgresDump(s.Host, s.Port, s.User, s.Password, s.Database), nil
	case "composite":
		var s compositeSource
		if err := json.Unmarshal(job.Source, &s); err != nil {
			return nil, fmt.Errorf("parse composite source: %w", err)
		}
		var parts []source.Part
		for _, p := range s.Paths {
			if p.Root == "" {
				continue
			}
			label := p.Label
			if label == "" {
				label = "files"
			}
			parts = append(parts, source.Part{Label: "files-" + label, Src: source.NewLocalFiles(p.Root)})
		}
		for _, d := range s.Databases {
			label := d.Label
			if label == "" {
				label = d.Database
			}
			var sub source.Source
			switch d.Engine {
			case "postgres":
				sub = source.NewPostgresDump(d.Host, d.Port, d.User, d.Password, d.Database)
			default: // mysql / mariadb
				sub = source.NewMySQLDump(d.Host, d.Port, d.User, d.Password, d.Database)
			}
			parts = append(parts, source.Part{Label: "db-" + label, Src: sub})
		}
		if len(parts) == 0 {
			return nil, fmt.Errorf("composite source: no paths or databases configured")
		}
		return source.NewComposite(parts), nil
	default:
		return nil, fmt.Errorf("source type %q not implemented yet", job.Type)
	}
}

// RetentionFromJob maps a job's retention payload to a kopia RetentionSpec.
func RetentionFromJob(r api.Retention) kopia.RetentionSpec {
	return kopia.RetentionSpec{
		KeepLatest:  r.KeepLatest,
		KeepHourly:  r.KeepHourly,
		KeepDaily:   r.KeepDaily,
		KeepWeekly:  r.KeepWeekly,
		KeepMonthly: r.KeepMonthly,
		KeepAnnual:  r.KeepAnnual,
	}
}

// Execute runs a single job against the given kopia client. The client must
// already have its per-repo ConfigFile and CacheDir set. When prune is true and
// ret is non-empty, retention is applied and old snapshots are expired.
// ExecOptions carries the optional post-backup steps for a run.
type ExecOptions struct {
	Prune       bool
	Verify      bool
	Maintenance bool
	Retention   kopia.RetentionSpec
	Excludes    []string
}

func Execute(ctx context.Context, c *kopia.Client, ensure EnsureFunc, src source.Source, password string, opts ExecOptions) (api.Report, error) {
	prune, ret, excludes := opts.Prune, opts.Retention, opts.Excludes
	if err := ensure(ctx, c); err != nil {
		return api.Report{Status: api.RunFailed, Log: "repo setup: " + err.Error()}, err
	}
	m, err := src.Materialize(ctx)
	if err != nil {
		return api.Report{Status: api.RunFailed, Log: "materialize: " + err.Error()}, err
	}
	defer func() { _ = m.Cleanup() }()

	if err := c.SetIgnore(ctx, m.Path, password, excludes); err != nil {
		fmt.Fprintln(os.Stderr, "set ignore:", err)
	}

	snap, err := c.Snapshot(ctx, m.Path, password)
	if err != nil {
		return api.Report{Status: api.RunFailed, Log: "snapshot: " + err.Error()}, err
	}

	log := fmt.Sprintf("%s snapshot %s: %d files, %d bytes", src.Kind(), snap.ID, snap.Files(), snap.Size())
	status := api.RunSuccess

	// Optional integrity check of the snapshot we just wrote.
	if opts.Verify {
		if err := c.Verify(ctx, snap.ID, password); err != nil {
			log += " | verify FAILED: " + err.Error()
			status = api.RunWarn
		} else {
			log += " | verified"
		}
	}

	// Auto cleanup: apply retention + expire old snapshots after the backup.
	if prune && !ret.Empty() {
		if err := c.ApplyRetention(ctx, m.Path, password, ret); err != nil {
			// The backup itself succeeded; a prune failure is a warning, not a loss.
			log += " | prune warning: " + err.Error()
			status = api.RunWarn
		} else {
			log += " | pruned per retention"
		}
	}

	// Repository maintenance (compaction + GC) to reclaim space.
	if opts.Maintenance {
		if err := c.Maintenance(ctx, password); err != nil {
			log += " | maintenance warning: " + err.Error()
			status = api.RunWarn
		} else {
			log += " | maintained"
		}
	}

	return api.Report{
		Status:     status,
		SnapshotID: snap.ID,
		BytesIn:    snap.Size(),
		Files:      snap.Files(),
		Log:        log,
	}, nil
}
