package source

import (
	"context"
	"fmt"
	"os"
	"os/exec"
	"path/filepath"
	"strings"
)

// Part is one named sub-source of a composite job.
type Part struct {
	// Label names the part; it becomes a top-level folder in the snapshot.
	Label string
	// Src is the underlying source (files, mysql, postgres, ...).
	Src Source
}

// Composite materializes several sub-sources into a single staging tree so the
// whole set is captured in one snapshot (one restore point). Each part lands
// under <staging>/<label>/. Directory parts are hardlink-copied when possible
// (near-free on the same filesystem, falling back to a real copy across
// filesystems); database dumps land as files. This keeps composite "whole
// server" jobs on the same single-snapshot flow as every other source, so
// browse and restore need no special handling.
type Composite struct {
	Parts []Part
}

// NewComposite builds a Composite source from its parts.
func NewComposite(parts []Part) *Composite { return &Composite{Parts: parts} }

// Kind implements Source.
func (c *Composite) Kind() string { return "composite" }

// Materialize implements Source. It stages every part under one temp dir and
// returns that dir for snapshotting; Cleanup tears down the staging tree and
// each part's own scratch space.
func (c *Composite) Materialize(ctx context.Context) (*Materialized, error) {
	if len(c.Parts) == 0 {
		return nil, fmt.Errorf("composite source: no parts")
	}

	staging, err := os.MkdirTemp("", "backup-composite-")
	if err != nil {
		return nil, fmt.Errorf("composite source: staging dir: %w", err)
	}

	var cleanups []func() error
	cleanupAll := func() error {
		for _, fn := range cleanups {
			if fn != nil {
				_ = fn()
			}
		}
		return os.RemoveAll(staging)
	}

	used := map[string]int{}
	for i, p := range c.Parts {
		m, err := p.Src.Materialize(ctx)
		if err != nil {
			_ = cleanupAll()
			return nil, fmt.Errorf("composite part %q: %w", p.Label, err)
		}
		cleanups = append(cleanups, m.Cleanup)

		label := sanitizeLabel(p.Label)
		if label == "" {
			label = fmt.Sprintf("part-%d", i+1)
		}
		if n := used[label]; n > 0 { // de-collide duplicate labels
			used[label] = n + 1
			label = fmt.Sprintf("%s-%d", label, n+1)
		} else {
			used[label] = 1
		}

		dst := filepath.Join(staging, label)
		if err := stageInto(ctx, m.Path, dst); err != nil {
			_ = cleanupAll()
			return nil, fmt.Errorf("composite stage %q: %w", p.Label, err)
		}
	}

	return &Materialized{Path: staging, Cleanup: cleanupAll}, nil
}

// stageInto places src (a file or directory) at dst, preferring hardlinks so a
// whole-server copy on the same filesystem costs inodes, not data blocks.
func stageInto(ctx context.Context, src, dst string) error {
	info, err := os.Stat(src)
	if err != nil {
		return err
	}
	if info.IsDir() {
		if err := os.MkdirAll(dst, 0o755); err != nil {
			return err
		}
		// Copy the directory *contents* into dst.
		if err := runCP(ctx, "-al", src+"/.", dst+"/"); err != nil {
			return runCP(ctx, "-a", src+"/.", dst+"/")
		}
		return nil
	}
	if err := os.MkdirAll(filepath.Dir(dst), 0o755); err != nil {
		return err
	}
	if err := runCP(ctx, "-al", src, dst); err != nil {
		return runCP(ctx, "-a", src, dst)
	}
	return nil
}

func runCP(ctx context.Context, args ...string) error {
	cmd := exec.CommandContext(ctx, "cp", args...)
	if out, err := cmd.CombinedOutput(); err != nil {
		return fmt.Errorf("cp %s: %w: %s", strings.Join(args, " "), err, strings.TrimSpace(string(out)))
	}
	return nil
}

// sanitizeLabel makes a label safe as a single path segment.
func sanitizeLabel(s string) string {
	s = strings.TrimSpace(s)
	repl := func(r rune) rune {
		switch {
		case r >= 'a' && r <= 'z', r >= 'A' && r <= 'Z', r >= '0' && r <= '9', r == '-', r == '_', r == '.':
			return r
		default:
			return '-'
		}
	}
	s = strings.Map(repl, s)
	return strings.Trim(s, "-.")
}
