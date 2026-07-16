// Package config loads and persists the agent's local configuration.
//
// The agent keeps a small JSON file (default ~/.config/backup/agent.json)
// holding how to reach the master node, its API credentials, and where the
// bundled kopia binary lives. Secrets for individual jobs are never persisted
// here; they arrive per-job from the master over TLS.
package config

import (
	"encoding/json"
	"errors"
	"fmt"
	"os"
	"path/filepath"
	"time"
)

// Config is the agent's on-disk configuration.
type Config struct {
	// MasterURL is the base URL of the Laravel control plane, e.g.
	// https://backup.example.com. The agent only ever dials outbound to it.
	MasterURL string `json:"master_url"`

	// APIKey authenticates this agent to the master. Issued at enrollment.
	APIKey string `json:"api_key"`

	// HostID is the master's identifier for this host, assigned at enrollment.
	HostID string `json:"host_id"`

	// KopiaPath is the path to the kopia binary the agent drives. When empty
	// the agent falls back to a binary named "kopia" next to itself, then PATH.
	KopiaPath string `json:"kopia_path,omitempty"`

	// PollInterval is how often the agent polls the master for due jobs.
	PollInterval Duration `json:"poll_interval"`
}

// Duration is a JSON-friendly time.Duration ("30s", "5m").
type Duration time.Duration

func (d Duration) MarshalJSON() ([]byte, error) {
	return json.Marshal(time.Duration(d).String())
}

func (d *Duration) UnmarshalJSON(b []byte) error {
	var s string
	if err := json.Unmarshal(b, &s); err != nil {
		return err
	}
	v, err := time.ParseDuration(s)
	if err != nil {
		return err
	}
	*d = Duration(v)
	return nil
}

// DefaultPath returns the standard config path, honoring XDG_CONFIG_HOME.
func DefaultPath() string {
	if dir := os.Getenv("BACKUP_CONFIG"); dir != "" {
		return dir
	}
	base, err := os.UserConfigDir()
	if err != nil || base == "" {
		base = filepath.Join(os.Getenv("HOME"), ".config")
	}
	return filepath.Join(base, "backup", "agent.json")
}

// Default returns a config populated with sensible defaults.
func Default() *Config {
	return &Config{PollInterval: Duration(30 * time.Second)}
}

// Load reads the config at path. A missing file returns a default config and
// ErrNotConfigured so callers can distinguish "never enrolled" from real errors.
func Load(path string) (*Config, error) {
	b, err := os.ReadFile(path)
	if errors.Is(err, os.ErrNotExist) {
		return Default(), ErrNotConfigured
	}
	if err != nil {
		return nil, fmt.Errorf("read config %s: %w", path, err)
	}
	cfg := Default()
	if err := json.Unmarshal(b, cfg); err != nil {
		return nil, fmt.Errorf("parse config %s: %w", path, err)
	}
	return cfg, nil
}

// Save writes the config to path with 0600 perms, creating parent dirs.
func (c *Config) Save(path string) error {
	if err := os.MkdirAll(filepath.Dir(path), 0o755); err != nil {
		return err
	}
	b, err := json.MarshalIndent(c, "", "  ")
	if err != nil {
		return err
	}
	return os.WriteFile(path, b, 0o600)
}

// Enrolled reports whether the agent has credentials to talk to the master.
func (c *Config) Enrolled() bool {
	return c.MasterURL != "" && c.APIKey != "" && c.HostID != ""
}

// ErrNotConfigured is returned by Load when no config file exists yet.
var ErrNotConfigured = errors.New("agent not configured")
