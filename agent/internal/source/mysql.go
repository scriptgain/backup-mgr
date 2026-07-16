package source

import (
	"context"
	"fmt"
	"os"
	"os/exec"
	"path/filepath"
)

// MySQLDump dumps a MySQL/MariaDB database to a spool directory that kopia then
// snapshots. The spool is removed by Cleanup after the snapshot. Requires the
// `mysqldump` binary on the host.
type MySQLDump struct {
	Host     string
	Port     string
	User     string
	Password string
	Database string
}

// NewMySQLDump builds a MySQLDump source.
func NewMySQLDump(host, port, user, password, database string) *MySQLDump {
	return &MySQLDump{Host: host, Port: port, User: user, Password: password, Database: database}
}

// Kind implements Source.
func (m *MySQLDump) Kind() string { return "mysql" }

// Materialize runs a consistent dump to a spool dir and returns it.
func (m *MySQLDump) Materialize(ctx context.Context) (*Materialized, error) {
	if m.Database == "" {
		return nil, fmt.Errorf("mysql source: database is required")
	}
	if _, err := exec.LookPath("mysqldump"); err != nil {
		return nil, fmt.Errorf("mysql source: mysqldump not found on host: %w", err)
	}

	spool, err := os.MkdirTemp("", "backup-mysql-*")
	if err != nil {
		return nil, err
	}
	cleanup := func() error { return os.RemoveAll(spool) }
	out := filepath.Join(spool, m.Database+".sql")

	args := []string{"--single-transaction", "--quick", "--routines", "--triggers", "--events"}
	if m.Host != "" {
		args = append(args, "-h", m.Host)
	}
	if m.Port != "" {
		args = append(args, "-P", m.Port)
	}
	if m.User != "" {
		args = append(args, "-u", m.User)
	}
	args = append(args, m.Database)

	f, err := os.Create(out)
	if err != nil {
		_ = cleanup()
		return nil, err
	}
	defer f.Close()

	cmd := exec.CommandContext(ctx, "mysqldump", args...)
	// Pass the password via env so it never shows in the process list.
	cmd.Env = os.Environ()
	if m.Password != "" {
		cmd.Env = append(cmd.Env, "MYSQL_PWD="+m.Password)
	}
	cmd.Stdout = f
	if err := cmd.Run(); err != nil {
		_ = cleanup()
		return nil, fmt.Errorf("mysqldump %s: %w", m.Database, err)
	}

	return &Materialized{Path: spool, Cleanup: cleanup}, nil
}
