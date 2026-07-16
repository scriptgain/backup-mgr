package source

import (
	"context"
	"errors"
	"fmt"
	"os"
	"os/exec"
	"strconv"
	"strings"
)

// wgetHint translates a wget exit code into a human-readable reason.
func wgetHint(err error) string {
	var ee *exec.ExitError
	if errors.As(err, &ee) {
		switch ee.ExitCode() {
		case 4:
			return "network failure — host or port unreachable"
		case 5:
			return "SSL/TLS handshake failed"
		case 6:
			return "authentication failed — check the username and password"
		case 8:
			return "server returned an error — the path may not exist or access was denied"
		default:
			return "wget exit " + strconv.Itoa(ee.ExitCode())
		}
	}
	return err.Error()
}

// FTP pulls a remote FTP tree to a spool directory that kopia then snapshots.
// A blank RemotePath means the whole account (root). Uses wget for a recursive
// mirror so no extra Go dependency is needed.
type FTP struct {
	Host       string
	Port       string
	User       string
	Pass       string
	RemotePath string
}

// NewFTP builds an FTP source.
func NewFTP(host, port, user, pass, remotePath string) *FTP {
	return &FTP{Host: host, Port: port, User: user, Pass: pass, RemotePath: remotePath}
}

// Kind implements Source.
func (f *FTP) Kind() string { return "ftp" }

// Materialize mirrors the remote path to a spool dir and returns it.
func (f *FTP) Materialize(ctx context.Context) (*Materialized, error) {
	if f.Host == "" {
		return nil, fmt.Errorf("ftp source: host is required")
	}
	if _, err := exec.LookPath("wget"); err != nil {
		return nil, fmt.Errorf("ftp source: wget not found on the gateway: %w", err)
	}

	spool, err := os.MkdirTemp("", "backup-ftp-*")
	if err != nil {
		return nil, err
	}
	cleanup := func() error { return os.RemoveAll(spool) }

	port := f.Port
	if port == "" {
		port = "21"
	}
	path := f.RemotePath
	if path == "" {
		path = "/"
	}
	if !strings.HasPrefix(path, "/") {
		path = "/" + path
	}
	if !strings.HasSuffix(path, "/") {
		path += "/" // trailing slash => treat as directory to mirror
	}
	url := fmt.Sprintf("ftp://%s:%s%s", f.Host, port, path)

	args := []string{
		"-q", "-r", "-np", "-nH", "-R", "index.html*",
		"--no-check-certificate", "--timeout=30", "--tries=2",
		"-P", spool,
	}
	user := f.User
	if user == "" {
		user = "anonymous"
	}
	args = append(args, "--user="+user, "--password="+f.Pass, url)

	cmd := exec.CommandContext(ctx, "wget", args...)
	if err := cmd.Run(); err != nil {
		_ = cleanup()
		return nil, fmt.Errorf("FTP to %s:%s failed: %s", f.Host, port, wgetHint(err))
	}

	return &Materialized{Path: spool, Cleanup: cleanup}, nil
}
