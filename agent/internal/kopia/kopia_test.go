package kopia

import (
	"context"
	"crypto/sha256"
	"encoding/hex"
	"io"
	"os"
	"path/filepath"
	"runtime"
	"sort"
	"strings"
	"testing"
	"time"
)

// repoBin returns the pinned kopia binary shipped in agent/bin.
func repoBin(t *testing.T) string {
	t.Helper()
	_, thisFile, _, _ := runtime.Caller(0)
	// this file: agent/internal/kopia/kopia_test.go -> agent/bin/kopia
	bin := filepath.Join(filepath.Dir(thisFile), "..", "..", "bin", "kopia")
	if _, err := os.Stat(bin); err != nil {
		t.Skipf("kopia binary not found at %s (run deploy/local/fetch-kopia.sh): %v", bin, err)
	}
	abs, _ := filepath.Abs(bin)
	return abs
}

// TestBackupRestore is the Phase 2 acceptance gate: back up a directory to a
// filesystem-backed repo and restore it byte-identically. S3 is only a backend
// swap on top of this, so proving it here proves the spine.
func TestBackupRestore(t *testing.T) {
	bin := repoBin(t)
	const password = "test-passphrase-not-a-secret"

	root := t.TempDir()
	repoDir := filepath.Join(root, "repo")
	cacheDir := filepath.Join(root, "cache")
	cfgFile := filepath.Join(root, "kopia.config")
	srcDir := filepath.Join(root, "src")
	dstDir := filepath.Join(root, "restore")

	seedTree(t, srcDir)

	c := New(bin, cfgFile, cacheDir)
	ctx, cancel := context.WithTimeout(context.Background(), 60*time.Second)
	defer cancel()

	if err := c.EnsureFilesystem(ctx, FilesystemRepo{Path: repoDir, Password: password}); err != nil {
		t.Fatalf("EnsureFilesystem: %v", err)
	}

	snap, err := c.Snapshot(ctx, srcDir, password)
	if err != nil {
		t.Fatalf("Snapshot: %v", err)
	}
	if snap.ID == "" {
		t.Fatalf("snapshot returned empty ID")
	}
	t.Logf("snapshot id=%s files=%d size=%d", snap.ID, snap.Files(), snap.Size())
	if snap.Files() == 0 {
		t.Fatalf("snapshot reported 0 files; manifest stats not parsed")
	}

	list, err := c.ListSnapshots(ctx, password, "")
	if err != nil {
		t.Fatalf("ListSnapshots: %v", err)
	}
	if len(list) != 1 {
		t.Fatalf("expected 1 snapshot, got %d", len(list))
	}

	if err := c.Restore(ctx, snap.ID, dstDir, password); err != nil {
		t.Fatalf("Restore: %v", err)
	}

	before := hashTree(t, srcDir)
	after := hashTree(t, dstDir)
	if before != after {
		t.Fatalf("restore mismatch:\n before=%s\n after =%s", before, after)
	}
}

// TestApplyRetention verifies that pruning keeps only the retained snapshots.
func TestApplyRetention(t *testing.T) {
	bin := repoBin(t)
	const password = "test-passphrase-not-a-secret"
	root := t.TempDir()
	c := New(bin, filepath.Join(root, "kopia.config"), filepath.Join(root, "cache"))
	src := filepath.Join(root, "src")
	seedTree(t, src)

	ctx, cancel := context.WithTimeout(context.Background(), 90*time.Second)
	defer cancel()

	if err := c.EnsureFilesystem(ctx, FilesystemRepo{Path: filepath.Join(root, "repo"), Password: password}); err != nil {
		t.Fatalf("EnsureFilesystem: %v", err)
	}
	// Make three snapshots of the same source.
	for i := 0; i < 3; i++ {
		if err := os.WriteFile(filepath.Join(src, "hello.txt"), []byte("v"+string(rune('a'+i))), 0o644); err != nil {
			t.Fatal(err)
		}
		if _, err := c.Snapshot(ctx, src, password); err != nil {
			t.Fatalf("snapshot %d: %v", i, err)
		}
	}
	if list, _ := c.ListSnapshots(ctx, password, src); len(list) != 3 {
		t.Fatalf("expected 3 snapshots before prune, got %d", len(list))
	}
	if err := c.ApplyRetention(ctx, src, password, RetentionSpec{KeepLatest: 1}); err != nil {
		t.Fatalf("ApplyRetention: %v", err)
	}
	list, err := c.ListSnapshots(ctx, password, src)
	if err != nil {
		t.Fatalf("ListSnapshots: %v", err)
	}
	if len(list) != 1 {
		t.Fatalf("expected 1 snapshot after keep-latest=1, got %d", len(list))
	}
}

// seedTree writes a small nested directory tree with varied content.
func seedTree(t *testing.T, dir string) {
	t.Helper()
	files := map[string]string{
		"hello.txt":            "hello world\n",
		"nested/a.txt":         strings.Repeat("A", 4096),
		"nested/deep/b.bin":    string([]byte{0, 1, 2, 3, 255, 254, 10, 13}),
		"nested/deep/empty.txt": "",
		"unicode-Ünïcödé.md":   "# Title\n\ncontent with ünïcödé\n",
	}
	for rel, content := range files {
		p := filepath.Join(dir, rel)
		if err := os.MkdirAll(filepath.Dir(p), 0o755); err != nil {
			t.Fatal(err)
		}
		if err := os.WriteFile(p, []byte(content), 0o644); err != nil {
			t.Fatal(err)
		}
	}
}

// hashTree returns a stable digest of a directory's relative paths + contents,
// so two trees compare equal iff their files and bytes match.
func hashTree(t *testing.T, dir string) string {
	t.Helper()
	var lines []string
	err := filepath.Walk(dir, func(p string, info os.FileInfo, err error) error {
		if err != nil {
			return err
		}
		if info.IsDir() {
			return nil
		}
		rel, _ := filepath.Rel(dir, p)
		f, err := os.Open(p)
		if err != nil {
			return err
		}
		defer f.Close()
		h := sha256.New()
		if _, err := io.Copy(h, f); err != nil {
			return err
		}
		lines = append(lines, rel+":"+hex.EncodeToString(h.Sum(nil)))
		return nil
	})
	if err != nil {
		t.Fatalf("hashTree %s: %v", dir, err)
	}
	sort.Strings(lines)
	sum := sha256.Sum256([]byte(strings.Join(lines, "\n")))
	return hex.EncodeToString(sum[:])
}
