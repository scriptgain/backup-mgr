package api

import (
	"context"
	"encoding/json"
	"io"
	"net/http"
	"net/http/httptest"
	"testing"
)

func TestClientFlow(t *testing.T) {
	const token = "one-time-token"
	const issuedKey = "perm-api-key"

	mux := http.NewServeMux()

	mux.HandleFunc("/api/agent/v1/enroll", func(w http.ResponseWriter, r *http.Request) {
		if r.Method != http.MethodPost {
			t.Errorf("enroll method = %s", r.Method)
		}
		if got := r.Header.Get("Authorization"); got != "" {
			t.Errorf("enroll should be unauthed, got %q", got)
		}
		var req EnrollRequest
		if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
			t.Fatalf("decode enroll: %v", err)
		}
		if req.Token != token {
			t.Errorf("token = %q, want %q", req.Token, token)
		}
		writeJSON(w, EnrollResponse{HostID: "h1", APIKey: issuedKey})
	})

	requireAuth := func(w http.ResponseWriter, r *http.Request) bool {
		if r.Header.Get("Authorization") != "Bearer "+issuedKey {
			http.Error(w, "unauthorized", http.StatusUnauthorized)
			return false
		}
		return true
	}

	pollCalls := 0
	mux.HandleFunc("/api/agent/v1/poll", func(w http.ResponseWriter, r *http.Request) {
		if !requireAuth(w, r) {
			return
		}
		pollCalls++
		if pollCalls == 1 {
			writeJSON(w, pollResponse{Job: &Job{
				RunID: "run1", JobID: "job1", Type: "files", Connector: "agent",
				Repository: Repository{Bucket: "b", Password: "p"},
				Source:     json.RawMessage(`{"root":"/data"}`),
			}})
			return
		}
		writeJSON(w, pollResponse{Job: nil}) // nothing due
	})

	var gotReport Report
	mux.HandleFunc("/api/agent/v1/runs/run1/report", func(w http.ResponseWriter, r *http.Request) {
		if !requireAuth(w, r) {
			return
		}
		if err := json.NewDecoder(r.Body).Decode(&gotReport); err != nil {
			t.Fatalf("decode report: %v", err)
		}
		w.WriteHeader(http.StatusNoContent)
	})

	mux.HandleFunc("/api/agent/v1/heartbeat", func(w http.ResponseWriter, r *http.Request) {
		if !requireAuth(w, r) {
			return
		}
		w.WriteHeader(http.StatusNoContent)
	})

	srv := httptest.NewServer(mux)
	defer srv.Close()

	ctx := context.Background()

	// Enroll (unauthed) adopts the issued key.
	c := New(srv.URL, "")
	er, err := c.Enroll(ctx, EnrollRequest{Token: token, Hostname: "host", OS: "linux", Arch: "amd64", AgentVersion: "test"})
	if err != nil {
		t.Fatalf("Enroll: %v", err)
	}
	if er.HostID != "h1" || c.apiKey != issuedKey {
		t.Fatalf("enroll adopted wrong creds: %+v key=%q", er, c.apiKey)
	}

	// First poll returns a job.
	job, err := c.Poll(ctx)
	if err != nil {
		t.Fatalf("Poll: %v", err)
	}
	if job == nil || job.RunID != "run1" || job.Connector != "agent" {
		t.Fatalf("unexpected job: %+v", job)
	}

	// Second poll returns nothing.
	job2, err := c.Poll(ctx)
	if err != nil {
		t.Fatalf("Poll2: %v", err)
	}
	if job2 != nil {
		t.Fatalf("expected no job, got %+v", job2)
	}

	// Report the run result.
	if err := c.Report(ctx, "run1", Report{Status: RunSuccess, SnapshotID: "snap1", BytesIn: 100}); err != nil {
		t.Fatalf("Report: %v", err)
	}
	if gotReport.Status != RunSuccess || gotReport.SnapshotID != "snap1" {
		t.Fatalf("master received wrong report: %+v", gotReport)
	}

	// Heartbeat.
	if _, err := c.Heartbeat(ctx, "test"); err != nil {
		t.Fatalf("Heartbeat: %v", err)
	}
}

// TestPollUnauthorized ensures a bad key surfaces as an error.
func TestPollUnauthorized(t *testing.T) {
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		http.Error(w, "nope", http.StatusUnauthorized)
	}))
	defer srv.Close()

	c := New(srv.URL, "wrong-key")
	if _, err := c.Poll(context.Background()); err == nil {
		t.Fatal("expected error for unauthorized poll, got nil")
	}
}

func writeJSON(w http.ResponseWriter, v any) {
	w.Header().Set("Content-Type", "application/json")
	b, _ := json.Marshal(v)
	_, _ = io.Writer(w).Write(b)
}
