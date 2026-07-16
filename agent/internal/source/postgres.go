package source

import (
	"context"
	"fmt"
	"os"
	"os/exec"
	"path/filepath"
)

// PostgresDump dumps a PostgreSQL database to a spool directory for kopia to
// snapshot. Requires the `pg_dump` binary on the host.
type PostgresDump struct {
	Host     string
	Port     string
	User     string
	Password string
	Database string
}

// NewPostgresDump builds a PostgresDump source.
func NewPostgresDump(host, port, user, password, database string) *PostgresDump {
	return &PostgresDump{Host: host, Port: port, User: user, Password: password, Database: database}
}

// Kind implements Source.
func (p *PostgresDump) Kind() string { return "postgres" }

// Materialize runs pg_dump to a spool dir and returns it.
func (p *PostgresDump) Materialize(ctx context.Context) (*Materialized, error) {
	if p.Database == "" {
		return nil, fmt.Errorf("postgres source: database is required")
	}
	if _, err := exec.LookPath("pg_dump"); err != nil {
		return nil, fmt.Errorf("postgres source: pg_dump not found on host: %w", err)
	}

	spool, err := os.MkdirTemp("", "backup-pg-*")
	if err != nil {
		return nil, err
	}
	cleanup := func() error { return os.RemoveAll(spool) }
	out := filepath.Join(spool, p.Database+".sql")

	args := []string{"-d", p.Database, "-f", out, "--no-owner", "--no-privileges"}
	if p.Host != "" {
		args = append(args, "-h", p.Host)
	}
	if p.Port != "" {
		args = append(args, "-p", p.Port)
	}
	if p.User != "" {
		args = append(args, "-U", p.User)
	}

	cmd := exec.CommandContext(ctx, "pg_dump", args...)
	cmd.Env = os.Environ()
	if p.Password != "" {
		cmd.Env = append(cmd.Env, "PGPASSWORD="+p.Password)
	}
	if err := cmd.Run(); err != nil {
		_ = cleanup()
		return nil, fmt.Errorf("pg_dump %s: %w", p.Database, err)
	}

	return &Materialized{Path: spool, Cleanup: cleanup}, nil
}
