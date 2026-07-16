// Package kopia is a thin wrapper around the kopia CLI.
//
// We deliberately drive the bundled kopia binary rather than importing kopia's
// internal Go packages, which are not a stable public API. Every operation
// shells out and, where possible, requests JSON output so results parse
// reliably. This keeps the agent decoupled from kopia's internals and lets us
// pin an exact kopia version per agent build.
//
// Each host/repo gets its own kopia config file (--config-file) and cache
// directory (KOPIA_CACHE_DIRECTORY) so multiple repositories never collide.
// The repository password and any S3 credentials are passed via environment
// per operation and never written to disk by the agent.
package kopia

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"os"
	"os/exec"
	"path/filepath"
	"strconv"
	"strings"
)

// Client runs kopia commands against a per-host config directory.
type Client struct {
	// Bin is the path to the kopia binary.
	Bin string
	// ConfigFile is the kopia repository config file for this host/repo.
	ConfigFile string
	// CacheDir is kopia's cache location for this repo.
	CacheDir string
}

// Locate resolves the kopia binary path. Preference order:
//  1. the explicit hint (from agent config), if it exists;
//  2. a binary named "kopia" sitting next to the agent executable;
//  3. "kopia" found on PATH.
func Locate(hint string) (string, error) {
	if hint != "" {
		if fi, err := os.Stat(hint); err == nil && !fi.IsDir() {
			return hint, nil
		}
	}
	if self, err := os.Executable(); err == nil {
		cand := filepath.Join(filepath.Dir(self), "kopia")
		if fi, err := os.Stat(cand); err == nil && !fi.IsDir() {
			return cand, nil
		}
	}
	if p, err := exec.LookPath("kopia"); err == nil {
		return p, nil
	}
	return "", fmt.Errorf("kopia binary not found (checked hint %q, alongside agent, and PATH)", hint)
}

// New builds a Client for the given binary and per-repo config/cache dirs.
func New(bin, configFile, cacheDir string) *Client {
	return &Client{Bin: bin, ConfigFile: configFile, CacheDir: cacheDir}
}

// baseEnv builds the environment kopia needs for an authenticated operation:
// the repository password and (if set) the per-repo cache directory.
func (c *Client) baseEnv(password string) []string {
	var env []string
	if password != "" {
		env = append(env, "KOPIA_PASSWORD="+password)
	}
	if c.CacheDir != "" {
		env = append(env, "KOPIA_CACHE_DIRECTORY="+c.CacheDir)
	}
	return env
}

// run executes kopia with the given args plus the per-repo config flag.
// extraEnv is appended to the current environment.
func (c *Client) run(ctx context.Context, extraEnv []string, args ...string) (string, error) {
	full := args
	if c.ConfigFile != "" {
		full = append([]string{"--config-file", c.ConfigFile}, full...)
	}
	cmd := exec.CommandContext(ctx, c.Bin, full...)
	cmd.Env = append(os.Environ(), extraEnv...)
	var stdout, stderr bytes.Buffer
	cmd.Stdout = &stdout
	cmd.Stderr = &stderr
	if err := cmd.Run(); err != nil {
		return stdout.String(), fmt.Errorf("kopia %s: %w: %s",
			strings.Join(args, " "), err, strings.TrimSpace(stderr.String()))
	}
	return stdout.String(), nil
}

// Version returns the kopia binary's version string.
func (c *Client) Version(ctx context.Context) (string, error) {
	out, err := c.run(ctx, nil, "--version")
	return strings.TrimSpace(out), err
}

// Connected reports whether the config file already points at a live repo.
func (c *Client) Connected(ctx context.Context, password string) bool {
	_, err := c.run(ctx, c.baseEnv(password), "repository", "status")
	return err == nil
}

// FilesystemRepo describes a local filesystem-backed repository. Used for local
// development and tests; production uses S3Repo.
type FilesystemRepo struct {
	Path     string
	Password string
}

// EnsureFilesystem connects to the filesystem repo at r.Path, creating it if it
// does not yet exist. Idempotent.
func (c *Client) EnsureFilesystem(ctx context.Context, r FilesystemRepo) error {
	if c.Connected(ctx, r.Password) {
		return nil
	}
	env := c.baseEnv(r.Password)
	// Try to connect first (repo may exist but this config file is fresh).
	if _, err := c.run(ctx, env, "repository", "connect", "filesystem", "--path", r.Path); err == nil {
		return nil
	}
	if err := os.MkdirAll(r.Path, 0o700); err != nil {
		return err
	}
	_, err := c.run(ctx, env, "repository", "create", "filesystem", "--path", r.Path)
	return err
}

// S3Repo describes an S3-compatible backed repository.
type S3Repo struct {
	Endpoint     string
	Region       string
	Bucket       string
	Prefix       string
	AccessKeyID  string
	SecretKey    string
	Password     string
	Compression  string // e.g. "zstd"; empty leaves kopia default
	DisableTLS   bool
}

// EnsureS3 connects to the S3 repo, creating it if it does not exist. Idempotent.
func (c *Client) EnsureS3(ctx context.Context, r S3Repo) error {
	if c.Connected(ctx, r.Password) {
		return nil
	}
	env := c.baseEnv(r.Password)
	base := []string{
		"s3",
		"--endpoint", r.Endpoint,
		"--bucket", r.Bucket,
		"--access-key", r.AccessKeyID,
		"--secret-access-key", r.SecretKey,
	}
	if r.Region != "" {
		base = append(base, "--region", r.Region)
	}
	if r.Prefix != "" {
		base = append(base, "--prefix", r.Prefix)
	}
	if r.DisableTLS {
		base = append(base, "--disable-tls")
	}
	if _, err := c.run(ctx, env, append([]string{"repository", "connect"}, base...)...); err == nil {
		return nil
	}
	if _, err := c.run(ctx, env, append([]string{"repository", "create"}, base...)...); err != nil {
		return err
	}
	if r.Compression != "" {
		if _, err := c.run(ctx, env, "policy", "set", "--global", "--compression", r.Compression); err != nil {
			return err
		}
	}
	return nil
}

// SnapshotResult is the subset of kopia's snapshot manifest we care about.
// kopia reports per-snapshot totals under rootEntry.summ, not a top-level stats
// object, so the counts we surface (files/dirs/size) come from there.
type SnapshotResult struct {
	ID     string `json:"id"`
	Source struct {
		Host     string `json:"host"`
		UserName string `json:"userName"`
		Path     string `json:"path"`
	} `json:"source"`
	StartTime string `json:"startTime"`
	EndTime   string `json:"endTime"`
	RootEntry struct {
		ObjectID string `json:"obj"`
		Summary  struct {
			Size      int64 `json:"size"`
			Files     int   `json:"files"`
			Symlinks  int   `json:"symlinks"`
			Dirs      int   `json:"dirs"`
			NumFailed int   `json:"numFailed"`
		} `json:"summ"`
	} `json:"rootEntry"`
}

// Size returns the total logical size of the snapshot in bytes.
func (s *SnapshotResult) Size() int64 { return s.RootEntry.Summary.Size }

// Files returns the number of files captured in the snapshot.
func (s *SnapshotResult) Files() int { return s.RootEntry.Summary.Files }

// Snapshot creates a snapshot of source and returns its manifest.
func (c *Client) Snapshot(ctx context.Context, source, password string) (*SnapshotResult, error) {
	out, err := c.run(ctx, c.baseEnv(password), "snapshot", "create", source, "--json")
	if err != nil {
		return nil, err
	}
	// kopia may emit one manifest object, or (rarely) a stream; take the last
	// complete JSON object on stdout.
	obj := lastJSONObject(out)
	if obj == "" {
		return nil, fmt.Errorf("no snapshot manifest in output: %s", strings.TrimSpace(out))
	}
	var res SnapshotResult
	if err := json.Unmarshal([]byte(obj), &res); err != nil {
		return nil, fmt.Errorf("parse snapshot manifest: %w", err)
	}
	return &res, nil
}

// ListSnapshots returns all snapshots, optionally filtered to a source path.
func (c *Client) ListSnapshots(ctx context.Context, password, source string) ([]SnapshotResult, error) {
	args := []string{"snapshot", "list", "--json"}
	if source != "" {
		args = append(args, source)
	}
	out, err := c.run(ctx, c.baseEnv(password), args...)
	if err != nil {
		return nil, err
	}
	var list []SnapshotResult
	if err := json.Unmarshal([]byte(out), &list); err != nil {
		return nil, fmt.Errorf("parse snapshot list: %w", err)
	}
	return list, nil
}

// Restore restores a snapshot (by manifest ID or root object ID) to target.
func (c *Client) Restore(ctx context.Context, snapshotID, target, password string) error {
	_, err := c.run(ctx, c.baseEnv(password), "restore", snapshotID, target)
	return err
}

// RestorePath restores a single path within a snapshot to dest.
func (c *Client) RestorePath(ctx context.Context, snapshotID, path, dest, password string) error {
	src := strings.TrimSuffix(snapshotID, "/") + "/" + strings.TrimPrefix(path, "/")
	_, err := c.run(ctx, c.baseEnv(password), "restore", src, dest)
	return err
}

// FileEntry is one entry in a snapshot's file listing.
type FileEntry struct {
	Path string `json:"path"`
	Size int64  `json:"size"`
	Dir  bool   `json:"dir"`
}

// ListFiles returns the (recursive) file listing of a snapshot, capped at limit.
func (c *Client) ListFiles(ctx context.Context, snapshotID, password string, limit int) ([]FileEntry, error) {
	out, err := c.run(ctx, c.baseEnv(password), "ls", "-l", "-r", snapshotID)
	if err != nil {
		return nil, err
	}
	var entries []FileEntry
	for _, line := range strings.Split(out, "\n") {
		fields := strings.Fields(line)
		if len(fields) < 7 {
			continue
		}
		size, _ := strconv.ParseInt(fields[1], 10, 64)
		name := strings.Join(fields[6:], " ")
		entries = append(entries, FileEntry{
			Path: strings.TrimSuffix(name, "/"),
			Size: size,
			Dir:  strings.HasPrefix(fields[0], "d"),
		})
		if len(entries) >= limit {
			break
		}
	}
	return entries, nil
}

// SetIgnore adds ignore (exclude) rules to the source's kopia policy, so paths
// like /proc /sys are skipped in a full-system backup.
func (c *Client) SetIgnore(ctx context.Context, source, password string, patterns []string) error {
	if len(patterns) == 0 {
		return nil
	}
	args := []string{"policy", "set", source}
	for _, p := range patterns {
		if strings.TrimSpace(p) != "" {
			args = append(args, "--add-ignore", p)
		}
	}
	if len(args) == 3 {
		return nil
	}
	_, err := c.run(ctx, c.baseEnv(password), args...)
	return err
}

// RetentionSpec mirrors kopia's keep-N retention knobs.
type RetentionSpec struct {
	KeepLatest, KeepHourly, KeepDaily, KeepWeekly, KeepMonthly, KeepAnnual int
}

// Empty reports whether no retention values are set.
func (r RetentionSpec) Empty() bool {
	return r.KeepLatest+r.KeepHourly+r.KeepDaily+r.KeepWeekly+r.KeepMonthly+r.KeepAnnual == 0
}

// ApplyRetention sets the retention policy on source and expires snapshots that
// fall outside it (the "auto cleanup" / pruning step).
func (c *Client) ApplyRetention(ctx context.Context, source, password string, r RetentionSpec) error {
	if r.Empty() {
		return nil
	}
	env := c.baseEnv(password)
	args := []string{"policy", "set", source}
	add := func(flag string, n int) {
		if n > 0 {
			args = append(args, flag, strconv.Itoa(n))
		}
	}
	add("--keep-latest", r.KeepLatest)
	add("--keep-hourly", r.KeepHourly)
	add("--keep-daily", r.KeepDaily)
	add("--keep-weekly", r.KeepWeekly)
	add("--keep-monthly", r.KeepMonthly)
	add("--keep-annual", r.KeepAnnual)
	if _, err := c.run(ctx, env, args...); err != nil {
		return err
	}
	_, err := c.run(ctx, env, "snapshot", "expire", source, "--delete")
	return err
}

// Verify checks the integrity of a stored snapshot's contents.
func (c *Client) Verify(ctx context.Context, snapshotID, password string) error {
	_, err := c.run(ctx, c.baseEnv(password), "snapshot", "verify", snapshotID)
	return err
}

// Maintenance runs kopia's repository maintenance (compaction + GC) to reclaim
// space and keep the repo healthy.
func (c *Client) Maintenance(ctx context.Context, password string) error {
	_, err := c.run(ctx, c.baseEnv(password), "maintenance", "run", "--safety=full")
	return err
}

// lastJSONObject returns the last top-level {...} block in s, or "".
func lastJSONObject(s string) string {
	end := strings.LastIndex(s, "}")
	if end < 0 {
		return ""
	}
	depth := 0
	for i := end; i >= 0; i-- {
		switch s[i] {
		case '}':
			depth++
		case '{':
			depth--
			if depth == 0 {
				return s[i : end+1]
			}
		}
	}
	return ""
}
