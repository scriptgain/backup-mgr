// Package api is the agent's client for the master control plane.
//
// All traffic is agent-initiated outbound HTTPS. Authentication is a per-agent
// bearer API key (issued at enrollment); the /enroll call itself is unauthed and
// uses a one-time token. Endpoints live under /api/agent/v1 on the master; see
// api/openapi.yaml for the contract.
package api

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"strings"
	"time"
)

const apiPrefix = "/api/agent/v1"

// Client talks to one master control plane.
type Client struct {
	baseURL string // master root, e.g. https://backup.example.com
	apiKey  string
	hc      *http.Client
}

// New builds a Client. apiKey may be empty for the initial Enroll call.
func New(masterURL, apiKey string) *Client {
	return &Client{
		baseURL: strings.TrimRight(masterURL, "/"),
		apiKey:  apiKey,
		hc:      &http.Client{Timeout: 60 * time.Second},
	}
}

// WithHTTPClient overrides the underlying http.Client (used in tests).
func (c *Client) WithHTTPClient(hc *http.Client) *Client {
	c.hc = hc
	return c
}

func (c *Client) endpoint(path string) string {
	return c.baseURL + apiPrefix + path
}

// do performs a JSON request. When body is non-nil it is JSON-encoded; when out
// is non-nil a 2xx response body is decoded into it. auth toggles the bearer
// header. It returns the HTTP status and an error for non-2xx responses.
func (c *Client) do(ctx context.Context, method, path string, body, out any, auth bool) (int, error) {
	var rdr io.Reader
	if body != nil {
		b, err := json.Marshal(body)
		if err != nil {
			return 0, fmt.Errorf("encode request: %w", err)
		}
		rdr = bytes.NewReader(b)
	}
	req, err := http.NewRequestWithContext(ctx, method, c.endpoint(path), rdr)
	if err != nil {
		return 0, err
	}
	req.Header.Set("Accept", "application/json")
	if body != nil {
		req.Header.Set("Content-Type", "application/json")
	}
	if auth {
		if c.apiKey == "" {
			return 0, fmt.Errorf("%s %s: missing API key", method, path)
		}
		req.Header.Set("Authorization", "Bearer "+c.apiKey)
	}

	resp, err := c.hc.Do(req)
	if err != nil {
		return 0, err
	}
	defer resp.Body.Close()

	if resp.StatusCode < 200 || resp.StatusCode >= 300 {
		snippet, _ := io.ReadAll(io.LimitReader(resp.Body, 2048))
		return resp.StatusCode, fmt.Errorf("%s %s: %s: %s",
			method, path, resp.Status, strings.TrimSpace(string(snippet)))
	}
	if out != nil && resp.StatusCode != http.StatusNoContent {
		if err := json.NewDecoder(resp.Body).Decode(out); err != nil {
			return resp.StatusCode, fmt.Errorf("decode response: %w", err)
		}
	}
	return resp.StatusCode, nil
}

// Enroll trades a one-time token for permanent credentials. On success the
// caller should adopt the returned APIKey for all further calls.
func (c *Client) Enroll(ctx context.Context, req EnrollRequest) (*EnrollResponse, error) {
	var out EnrollResponse
	if _, err := c.do(ctx, http.MethodPost, "/enroll", req, &out, false); err != nil {
		return nil, err
	}
	c.apiKey = out.APIKey
	return &out, nil
}

// pollResponse wraps the optional job so "nothing due" is an explicit null.
type pollResponse struct {
	Job *Job `json:"job"`
}

// Poll fetches the next due job, or returns (nil, nil) when nothing is due.
func (c *Client) Poll(ctx context.Context) (*Job, error) {
	var out pollResponse
	status, err := c.do(ctx, http.MethodGet, "/poll", nil, &out, true)
	if err != nil {
		return nil, err
	}
	if status == http.StatusNoContent {
		return nil, nil
	}
	return out.Job, nil
}

// Report posts progress or the final result of a run.
func (c *Client) Report(ctx context.Context, runID string, r Report) error {
	_, err := c.do(ctx, http.MethodPost, "/runs/"+runID+"/report", r, nil, true)
	return err
}

// Heartbeat pings the master to update last_seen and report the agent version.
// It returns the master's response (optional update offer + poll cadence).
func (c *Client) Heartbeat(ctx context.Context, agentVersion string) (*HeartbeatResponse, error) {
	body := map[string]string{"agent_version": agentVersion}
	var out HeartbeatResponse
	if _, err := c.do(ctx, http.MethodPost, "/heartbeat", body, &out, true); err != nil {
		return nil, err
	}
	return &out, nil
}

// restorePollResponse wraps the optional restore task.
type restorePollResponse struct {
	Restore *RestoreTask `json:"restore"`
}

// PollRestore fetches the next queued restore, or (nil, nil) when none.
func (c *Client) PollRestore(ctx context.Context) (*RestoreTask, error) {
	var out restorePollResponse
	if _, err := c.do(ctx, http.MethodGet, "/restores/poll", nil, &out, true); err != nil {
		return nil, err
	}
	return out.Restore, nil
}

// ReportRestore posts the result of a restore task.
func (c *Client) ReportRestore(ctx context.Context, id, status, log string) error {
	body := map[string]string{"status": status, "log": log}
	_, err := c.do(ctx, http.MethodPost, "/restores/"+id+"/report", body, nil, true)
	return err
}

// ReportIndex uploads a snapshot's file listing for browsing in the UI.
func (c *Client) ReportIndex(ctx context.Context, runID string, files any) error {
	_, err := c.do(ctx, http.MethodPost, "/runs/"+runID+"/index", map[string]any{"files": files}, nil, true)
	return err
}

// PollSync fetches the next due file-sync folder for this gateway's Director.
func (c *Client) PollSync(ctx context.Context) (*SyncTask, error) {
	var out struct {
		Sync *SyncTask `json:"sync"`
	}
	if _, err := c.do(ctx, http.MethodGet, "/sync/poll", nil, &out, true); err != nil {
		return nil, err
	}
	return out.Sync, nil
}

// ReportSync posts the result of a sync task.
func (c *Client) ReportSync(ctx context.Context, id, status, result string) error {
	body := map[string]string{"status": status, "result": result}
	_, err := c.do(ctx, http.MethodPost, "/sync/"+id+"/report", body, nil, true)
	return err
}
