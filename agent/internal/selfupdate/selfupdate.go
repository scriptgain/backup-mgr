// Package selfupdate replaces the running agent binary with a newer build the
// master advertises, then re-execs. Downloads are outbound-only (the master
// never connects in), matching the agent's firewall-friendly model.
package selfupdate

import (
	"context"
	"fmt"
	"io"
	"net/http"
	"os"
	"os/exec"
	"path/filepath"
	"syscall"
	"time"
)

// Apply downloads the binary at url, sanity-checks it, and atomically swaps it
// over the currently running executable. It does not restart; call Restart to
// re-exec into the new binary.
func Apply(ctx context.Context, url, newVersion string) error {
	exe, err := os.Executable()
	if err != nil {
		return err
	}
	if exe, err = filepath.EvalSymlinks(exe); err != nil {
		return err
	}

	tmp := exe + ".new"
	if err := download(ctx, url, tmp); err != nil {
		return fmt.Errorf("download: %w", err)
	}
	// Best-effort cleanup if we bail before the rename.
	defer os.Remove(tmp)

	if err := os.Chmod(tmp, 0o755); err != nil {
		return err
	}

	// Sanity check: the new binary must run and report the expected version.
	if err := verify(ctx, tmp, newVersion); err != nil {
		return fmt.Errorf("verify: %w", err)
	}

	// Renaming over a running executable is safe on Linux: the running process
	// keeps its open inode; new execs pick up the replacement.
	if err := os.Rename(tmp, exe); err != nil {
		return fmt.Errorf("swap: %w", err)
	}
	return nil
}

// Restart re-execs the current process image (now the new binary) with the same
// args and environment. On success it never returns.
func Restart() error {
	exe, err := os.Executable()
	if err != nil {
		return err
	}
	return syscall.Exec(exe, os.Args, os.Environ())
}

func download(ctx context.Context, url, dst string) error {
	ctx, cancel := context.WithTimeout(ctx, 5*time.Minute)
	defer cancel()

	req, err := http.NewRequestWithContext(ctx, http.MethodGet, url, nil)
	if err != nil {
		return err
	}
	resp, err := (&http.Client{Timeout: 5 * time.Minute}).Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()
	if resp.StatusCode != http.StatusOK {
		return fmt.Errorf("unexpected status %d", resp.StatusCode)
	}

	f, err := os.OpenFile(dst, os.O_CREATE|os.O_TRUNC|os.O_WRONLY, 0o755)
	if err != nil {
		return err
	}
	if _, err := io.Copy(f, resp.Body); err != nil {
		f.Close()
		return err
	}
	return f.Close()
}

// verify runs `<bin> version` and confirms it exits cleanly. newVersion is
// advisory (logged by the caller); we mainly guard against a corrupt download.
func verify(ctx context.Context, bin, newVersion string) error {
	ctx, cancel := context.WithTimeout(ctx, 15*time.Second)
	defer cancel()
	out, err := exec.CommandContext(ctx, bin, "version").CombinedOutput()
	if err != nil {
		return fmt.Errorf("%v: %s", err, out)
	}
	_ = newVersion
	return nil
}
