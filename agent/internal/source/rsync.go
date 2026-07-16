package source

import (
	"context"
	"fmt"
	"os"
	"os/exec"
	"strings"
)

// RsyncSSH pulls a remote path over SSH (rsync) into a spool directory that
// kopia then snapshots. Covers the ssh/sftp/rsync connectors for hosts you have
// SSH access to. A blank RemotePath backs up the account's home directory.
type RsyncSSH struct {
	Host       string
	Port       string
	User       string
	Password   string // used only if no key + sshpass is available
	PrivateKey string
	RemotePath string
}

// NewRsyncSSH builds an rsync-over-SSH source.
func NewRsyncSSH(host, port, user, password, privateKey, remotePath string) *RsyncSSH {
	return &RsyncSSH{Host: host, Port: port, User: user, Password: password, PrivateKey: privateKey, RemotePath: remotePath}
}

// Kind implements Source.
func (r *RsyncSSH) Kind() string { return "rsync" }

// Materialize rsyncs the remote path to a spool dir and returns it.
func (r *RsyncSSH) Materialize(ctx context.Context) (*Materialized, error) {
	if r.Host == "" {
		return nil, fmt.Errorf("rsync source: host is required")
	}
	if _, err := exec.LookPath("rsync"); err != nil {
		return nil, fmt.Errorf("rsync source: rsync not found on the gateway: %w", err)
	}

	spool, err := os.MkdirTemp("", "backup-rsync-*")
	if err != nil {
		return nil, err
	}
	var keyFile string
	cleanup := func() error {
		if keyFile != "" {
			_ = os.Remove(keyFile)
		}
		return os.RemoveAll(spool)
	}

	port := r.Port
	if port == "" {
		port = "22"
	}
	sshOpts := []string{"ssh", "-p", port, "-o", "StrictHostKeyChecking=accept-new", "-o", "UserKnownHostsFile=/dev/null", "-o", "ConnectTimeout=15"}

	var rsyncPrefix []string
	if strings.TrimSpace(r.PrivateKey) != "" {
		kf, err := os.CreateTemp("", "backup-key-*")
		if err != nil {
			_ = cleanup()
			return nil, err
		}
		keyFile = kf.Name()
		key := r.PrivateKey
		if !strings.HasSuffix(key, "\n") {
			key += "\n"
		}
		if _, err := kf.WriteString(key); err != nil {
			kf.Close()
			_ = cleanup()
			return nil, err
		}
		kf.Close()
		_ = os.Chmod(keyFile, 0o600)
		sshOpts = append(sshOpts, "-i", keyFile, "-o", "IdentitiesOnly=yes", "-o", "BatchMode=yes")
	} else if r.Password != "" {
		if _, err := exec.LookPath("sshpass"); err != nil {
			_ = cleanup()
			return nil, fmt.Errorf("rsync source: password auth needs `sshpass` on the gateway, or use an SSH key")
		}
		rsyncPrefix = []string{"sshpass", "-p", r.Password}
	} else {
		sshOpts = append(sshOpts, "-o", "BatchMode=yes")
	}

	remote := r.RemotePath // blank => remote home directory
	src := fmt.Sprintf("%s@%s:%s", r.User, r.Host, remote)
	if remote != "" && !strings.HasSuffix(remote, "/") {
		src += "/"
	}

	args := append(rsyncPrefix, "rsync", "-az", "--timeout=60", "-e", strings.Join(sshOpts, " "), src, spool+"/")
	cmd := exec.CommandContext(ctx, args[0], args[1:]...)
	if out, err := cmd.CombinedOutput(); err != nil {
		_ = cleanup()
		return nil, fmt.Errorf("rsync from %s failed: %w: %s", src, err, strings.TrimSpace(string(out)))
	}

	return &Materialized{Path: spool, Cleanup: cleanup}, nil
}
