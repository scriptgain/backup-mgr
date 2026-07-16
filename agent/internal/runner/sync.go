package runner

// File sync: mirror one main folder out to many target hosts. The gateway
// materializes the main source into a local path (reusing the same connectors
// as backups), then rsyncs it to each target. Local/agent targets on the same
// box are written directly; ssh/sftp/rsync targets go over SSH.

import (
	"context"
	"fmt"
	"os"
	"os/exec"
	"strings"

	"github.com/thelonelyfrog/backup/agent/internal/api"
	"github.com/thelonelyfrog/backup/agent/internal/source"
)

// ExecuteSync mirrors the task's main source to every target, returning a
// per-target result log and the first error encountered (nil if all succeeded).
func ExecuteSync(ctx context.Context, task *api.SyncTask) (string, error) {
	src, err := sourceForEndpoint(task.Source, "main source")
	if err != nil {
		return "", err
	}
	m, err := src.Materialize(ctx)
	if err != nil {
		return "", fmt.Errorf("read main source %s: %w", task.Source.Path, err)
	}
	defer m.Cleanup()

	if len(task.Targets) == 0 {
		return "", fmt.Errorf("no targets configured")
	}

	var lines []string
	var firstErr error
	for _, t := range task.Targets {
		var perr error
		switch t.Connector {
		case "local", "agent", "":
			perr = pushLocal(ctx, m.Path, t.Path, task.DeleteExtra)
		case "ssh", "sftp", "rsync":
			perr = pushRsyncSSH(ctx, m.Path, t, task.DeleteExtra)
		default:
			perr = fmt.Errorf("sync to %s targets is not supported yet", t.Connector)
		}
		if perr != nil {
			lines = append(lines, fmt.Sprintf("FAILED %s: %v", t.Path, perr))
			if firstErr == nil {
				firstErr = perr
			}
			continue
		}
		lines = append(lines, fmt.Sprintf("OK %s", t.Path))
	}
	return strings.Join(lines, "\n"), firstErr
}

func sourceForEndpoint(ep api.SyncEndpoint, role string) (source.Source, error) {
	switch ep.Connector {
	case "local", "agent", "":
		return source.NewLocalFiles(ep.Path), nil
	case "ssh", "sftp", "rsync":
		if ep.Transport == nil {
			return nil, fmt.Errorf("%s: missing SSH transport", role)
		}
		t := ep.Transport
		return source.NewRsyncSSH(t.Host, t.Port, t.Username, t.Secret, t.PrivateKey, ep.Path), nil
	case "ftp":
		if ep.Transport == nil {
			return nil, fmt.Errorf("%s: missing FTP transport", role)
		}
		t := ep.Transport
		return source.NewFTP(t.Host, t.Port, t.Username, t.Secret, ep.Path), nil
	}
	return nil, fmt.Errorf("%s: unsupported connector %q", role, ep.Connector)
}

// pushLocal mirrors srcPath into a directory on the gateway's own filesystem.
func pushLocal(ctx context.Context, srcPath, dst string, del bool) error {
	if dst == "" || dst == "/" {
		return fmt.Errorf("refusing to sync to %q", dst)
	}
	if _, err := exec.LookPath("rsync"); err != nil {
		return fmt.Errorf("rsync not found on the gateway")
	}
	if err := os.MkdirAll(dst, 0o755); err != nil {
		return err
	}
	args := []string{"-rlptDz"}
	if del {
		args = append(args, "--delete")
	}
	args = append(args, withSlash(srcPath), withSlash(dst))
	if out, err := exec.CommandContext(ctx, "rsync", args...).CombinedOutput(); err != nil {
		return fmt.Errorf("%v: %s", err, strings.TrimSpace(string(out)))
	}
	return nil
}

// pushRsyncSSH mirrors srcPath to a target host over SSH.
func pushRsyncSSH(ctx context.Context, srcPath string, ep api.SyncEndpoint, del bool) error {
	tr := ep.Transport
	if tr == nil || tr.Host == "" {
		return fmt.Errorf("missing SSH transport")
	}
	if ep.Path == "" || ep.Path == "/" {
		return fmt.Errorf("refusing to sync to %q", ep.Path)
	}
	if _, err := exec.LookPath("rsync"); err != nil {
		return fmt.Errorf("rsync not found on the gateway")
	}

	port := tr.Port
	if port == "" {
		port = "22"
	}
	sshOpts := []string{"ssh", "-p", port, "-o", "StrictHostKeyChecking=accept-new", "-o", "UserKnownHostsFile=/dev/null", "-o", "ConnectTimeout=15"}

	var prefix []string
	var keyFile string
	defer func() {
		if keyFile != "" {
			_ = os.Remove(keyFile)
		}
	}()

	if strings.TrimSpace(tr.PrivateKey) != "" {
		kf, err := os.CreateTemp("", "backup-key-*")
		if err != nil {
			return err
		}
		keyFile = kf.Name()
		key := tr.PrivateKey
		if !strings.HasSuffix(key, "\n") {
			key += "\n"
		}
		if _, err := kf.WriteString(key); err != nil {
			kf.Close()
			return err
		}
		kf.Close()
		_ = os.Chmod(keyFile, 0o600)
		sshOpts = append(sshOpts, "-i", keyFile, "-o", "IdentitiesOnly=yes", "-o", "BatchMode=yes")
	} else if tr.Secret != "" {
		if _, err := exec.LookPath("sshpass"); err != nil {
			return fmt.Errorf("password auth needs `sshpass` on the gateway, or use an SSH key")
		}
		prefix = []string{"sshpass", "-p", tr.Secret}
	} else {
		sshOpts = append(sshOpts, "-o", "BatchMode=yes")
	}

	remote := fmt.Sprintf("%s@%s:%s", tr.Username, tr.Host, withSlash(ep.Path))
	args := append(prefix, "rsync", "-rlptDz", "--timeout=60",
		"--rsync-path", fmt.Sprintf("mkdir -p %s && rsync", shellQuote(ep.Path)),
		"-e", strings.Join(sshOpts, " "))
	if del {
		args = append(args, "--delete")
	}
	args = append(args, withSlash(srcPath), remote)

	if out, err := exec.CommandContext(ctx, args[0], args[1:]...).CombinedOutput(); err != nil {
		return fmt.Errorf("%v: %s", err, strings.TrimSpace(string(out)))
	}
	return nil
}

func withSlash(p string) string {
	if strings.HasSuffix(p, "/") {
		return p
	}
	return p + "/"
}

func shellQuote(p string) string {
	return "'" + strings.ReplaceAll(p, "'", `'\''`) + "'"
}
