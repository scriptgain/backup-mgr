package source

import (
	"context"
	"fmt"
	"os"
)

// LocalFiles snapshots a directory on the agent's own host in place (the
// connector=agent path). No spool is used, so Cleanup is a no-op.
type LocalFiles struct {
	// Root is the directory to back up.
	Root string
}

// NewLocalFiles builds a LocalFiles source.
func NewLocalFiles(root string) *LocalFiles { return &LocalFiles{Root: root} }

// Kind implements Source.
func (f *LocalFiles) Kind() string { return "files" }

// Materialize implements Source. It validates the path exists and snapshots it
// directly; kopia handles excludes via repository/source policies.
func (f *LocalFiles) Materialize(_ context.Context) (*Materialized, error) {
	if f.Root == "" {
		return nil, fmt.Errorf("files source: empty root path")
	}
	if _, err := os.Stat(f.Root); err != nil {
		return nil, fmt.Errorf("files source: %w", err)
	}
	return &Materialized{Path: f.Root, Cleanup: NoCleanup}, nil
}
