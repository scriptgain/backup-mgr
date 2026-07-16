package api

import "encoding/json"

// EnrollRequest is sent once to trade a one-time token for a permanent API key.
type EnrollRequest struct {
	Token        string `json:"token"`
	Hostname     string `json:"hostname"`
	OS           string `json:"os"`
	Arch         string `json:"arch"`
	AgentVersion string `json:"agent_version"`
}

// EnrollResponse carries the credentials the agent persists after enrollment.
type EnrollResponse struct {
	HostID string `json:"host_id"`
	APIKey string `json:"api_key"`
}

// Repository is the per-job storage target and secrets. Delivered per job and
// never persisted by the agent.
type Repository struct {
	Backend         string `json:"backend"`         // s3|filesystem
	FilesystemPath  string `json:"filesystem_path"` // for backend=filesystem
	S3Endpoint      string `json:"s3_endpoint"`
	Region          string `json:"region"`
	Bucket          string `json:"bucket"`
	Prefix          string `json:"prefix"`
	AccessKeyID     string `json:"access_key_id"`
	SecretAccessKey string `json:"secret_access_key"`
	Password        string `json:"password"` // kopia repository password
	Compression     string `json:"compression"`
}

// Retention mirrors kopia's keep-N retention knobs.
type Retention struct {
	KeepLatest  int `json:"keep_latest"`
	KeepHourly  int `json:"keep_hourly"`
	KeepDaily   int `json:"keep_daily"`
	KeepWeekly  int `json:"keep_weekly"`
	KeepMonthly int `json:"keep_monthly"`
	KeepAnnual  int `json:"keep_annual"`
}

// Job is a single unit of work handed to the agent by the master.
type Job struct {
	RunID          string          `json:"run_id"`
	JobID          string          `json:"job_id"`
	Type           string          `json:"type"`      // files|mysql|postgres|composite
	Connector      string          `json:"connector"` // agent|sftp|rsync|ftp|s3
	PruneAfterBackup bool          `json:"prune_after_backup"`
	VerifyAfterBackup bool         `json:"verify_after_backup"`
	AutoMaintenance  bool          `json:"auto_maintenance"`
	ExecutorHostID string          `json:"executor_host_id,omitempty"`
	Transport      json.RawMessage `json:"transport,omitempty"` // agentless connection details
	Repository     Repository      `json:"repository"`
	Source         json.RawMessage `json:"source"` // type-specific (paths+excludes, or DB creds)
	Retention      Retention       `json:"retention"`
}

// RestoreTask is a queued restore handed to the agent.
type RestoreTask struct {
	ID         string     `json:"id"`
	SnapshotID string     `json:"snapshot_id"`
	TargetPath string     `json:"target_path"`
	Paths      []string   `json:"paths"` // specific files/dirs; empty = whole snapshot
	Repository Repository `json:"repository"`
}

// Transport carries agentless connection details for one host.
type Transport struct {
	Type       string `json:"type"`
	Host       string `json:"host"`
	Port       string `json:"port"`
	Username   string `json:"username"`
	Secret     string `json:"secret"`
	PrivateKey string `json:"private_key"`
}


// UpdateInfo advertises a newer agent build the master wants installed.
type UpdateInfo struct {
	Version string `json:"version"`
	URL     string `json:"url"`
}

// HeartbeatResponse is returned from /heartbeat; Update is non-nil when the
// master is offering a newer agent binary and auto-update is enabled.
// PollIntervalSeconds, when > 0, is the master-configured poll cadence the
// agent should adopt.
type HeartbeatResponse struct {
	Update              *UpdateInfo `json:"update"`
	PollIntervalSeconds int         `json:"poll_interval_seconds,omitempty"`
}

// RunStatus is the lifecycle state reported back for a run.
type RunStatus string

const (
	RunRunning RunStatus = "running"
	RunSuccess RunStatus = "success"
	RunWarn    RunStatus = "warn"
	RunFailed  RunStatus = "failed"
)

// Report is progress or the final result of a run, posted to the master.
type Report struct {
	Status        RunStatus `json:"status"`
	BytesIn       int64     `json:"bytes_in"`
	BytesUploaded int64     `json:"bytes_uploaded"`
	Files         int       `json:"files,omitempty"`
	SnapshotID    string    `json:"snapshot_id,omitempty"`
	Log           string    `json:"log,omitempty"`
}
