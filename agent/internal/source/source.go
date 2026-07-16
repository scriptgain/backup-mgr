// Package source turns a job's data source into a local path kopia can snapshot.
//
// Every connector, local or agentless, implements the same contract: Materialize
// produces a filesystem path representing the data to back up, plus a Cleanup to
// release any scratch space afterward. This unifies local files, database dumps,
// and agentless pulls (sftp/rsync/ftp/s3) under one flow:
//
//	m, err := src.Materialize(ctx)
//	defer m.Cleanup()
//	client.Snapshot(ctx, m.Path, password)
package source

import "context"

// Materialized is a local path ready to be snapshotted.
type Materialized struct {
	// Path is what kopia snapshots.
	Path string
	// Cleanup releases scratch resources (spool dirs, dump files). Never nil;
	// use NoCleanup for sources that snapshot data in place.
	Cleanup func() error
}

// Source materializes a job's data into a snapshottable local path.
type Source interface {
	// Materialize prepares the data and returns its local path.
	Materialize(ctx context.Context) (*Materialized, error)
	// Kind is the data kind for logging/reporting (files, mysql, ...).
	Kind() string
}

// NoCleanup is a Cleanup that does nothing, for in-place sources.
func NoCleanup() error { return nil }
