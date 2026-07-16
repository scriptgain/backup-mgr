<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Host;
use App\Models\Restore;
use App\Models\Run;
use App\Models\Setting;
use App\Models\SyncFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AgentController extends Controller
{
    /** Trade a one-time enrollment token for a permanent agent API key. */
    public function enroll(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'hostname' => ['nullable', 'string'],
            'os' => ['nullable', 'string'],
            'arch' => ['nullable', 'string'],
            'agent_version' => ['nullable', 'string'],
        ]);

        $host = Host::where('enrollment_token', hash('sha256', $data['token']))->first();
        if (! $host) {
            return response()->json(['message' => 'Invalid or used enrollment token.'], 401);
        }

        $plainKey = 'vlta_' . Str::random(48);
        $host->forceFill([
            'api_key' => hash('sha256', $plainKey),
            'enrollment_token' => null,
            'status' => 'online',
            'os' => $data['os'] ?? $host->os,
            'arch' => $data['arch'] ?? $host->arch,
            'agent_version' => $data['agent_version'] ?? $host->agent_version,
            'hostname' => $host->hostname ?: ($data['hostname'] ?? null),
            'last_seen_at' => now(),
        ])->save();

        return response()->json(['host_id' => (string) $host->id, 'api_key' => $plainKey]);
    }

    /** Return the next queued run for this host, or {job:null}. */
    public function poll(Request $request)
    {
        $host = $request->attributes->get('agent_host');
        $host->forceFill(['last_seen_at' => now(), 'status' => 'online'])->save();

        // This agent runs jobs for its own host (agent connector) AND acts as the
        // gateway for agentless hosts (ftp/sftp/rsync/ssh) in the same Director.
        $run = Run::where('status', 'queued')
            ->whereHas('job', function ($q) use ($host) {
                $q->where('enabled', true)->where(function ($w) use ($host) {
                    $w->where('host_id', $host->id)
                        ->orWhereHas('host', function ($h) use ($host) {
                            $h->where('director_id', $host->director_id)
                                ->whereIn('connection_type', ['ftp', 'sftp', 'rsync', 'ssh']);
                        });
                });
            })
            ->orderBy('id')
            ->with('job.repository', 'job.retentionPolicy', 'job.host')
            ->first();

        if (! $run) {
            return response()->json(['job' => null]);
        }

        $run->forceFill(['status' => 'running', 'started_at' => now()])->save();
        $job = $run->job;
        $s = Setting::map();

        return response()->json(['job' => [
            'run_id' => (string) $run->id,
            'job_id' => (string) $job->id,
            'type' => $job->type,
            'connector' => $job->connector,
            // Prune per the job, unless the global override forces it fleet-wide.
            'prune_after_backup' => (bool) $job->prune_after_backup || ($s['prune_all_jobs'] ?? '0') === '1',
            // Global post-backup policies from General settings.
            'verify_after_backup' => ($s['verify_after_backup'] ?? '0') === '1',
            // Maintenance is gated by the configured window (Settings → Maintenance).
            'auto_maintenance' => \App\Http\Controllers\MaintenanceController::allowedNow($s),
            'repository' => $this->repoPayload($job->repository),
            'source' => $job->source ?: new \stdClass,
            'transport' => $this->transportPayload($job->host),
            'retention' => $this->retentionPayload($job->retentionPolicy),
        ]]);
    }

    /** Record progress or the final result of a run. */
    public function report(Request $request, Run $run)
    {
        $data = $request->validate([
            'status' => ['required', 'in:running,success,warn,failed'],
            'bytes_in' => ['nullable', 'integer'],
            'bytes_uploaded' => ['nullable', 'integer'],
            'files' => ['nullable', 'integer'],
            'snapshot_id' => ['nullable', 'string'],
            'log' => ['nullable', 'string'],
        ]);

        $update = ['status' => $data['status']];
        foreach (['bytes_in', 'bytes_uploaded', 'files', 'snapshot_id', 'log'] as $k) {
            if (array_key_exists($k, $data) && $data[$k] !== null) {
                $update[$k] = $data[$k];
            }
        }
        if (in_array($data['status'], ['success', 'warn', 'failed'])) {
            $update['finished_at'] = now();
        }
        if ($data['status'] === 'failed') {
            $update['error'] = $data['log'] ?? 'Run failed.';
        }
        $run->forceFill($update)->save();

        if ($data['status'] === 'failed') {
            $this->notifyFailure($run);
        }

        return response()->noContent();
    }

    /** Email the configured address when a run fails. Best effort. */
    private function notifyFailure(Run $run): void
    {
        if (Setting::get('notifications_enabled') !== '1') {
            return;
        }
        $to = Setting::get('notify_email');
        if (! $to) {
            return;
        }
        $run->loadMissing('job.host');
        $job = $run->job;
        $body = "A backup run failed.\n\n"
            . 'Job: ' . ($job?->name ?? '—') . "\n"
            . 'Host: ' . ($job?->host?->name ?? '—') . "\n"
            . 'When: ' . now()->toDayDateTimeString() . "\n\n"
            . 'Error: ' . ($run->error ?: 'Unknown') . "\n";
        try {
            Mail::raw($body, function ($m) use ($to, $job) {
                $m->to($to)->subject('[' . config('brand.name') . '] Backup Failed: ' . ($job?->name ?? 'job'));
            });
        } catch (\Throwable $e) {
            // Never let a mail failure break the agent's report.
        }
    }

    /** Return the next queued restore for this host, or {restore:null}. */
    public function restorePoll(Request $request)
    {
        $host = $request->attributes->get('agent_host');
        // This agent restores to its own host, and acts as the gateway for
        // agentless hosts (ftp/sftp/rsync/ssh) in the same Director.
        $restore = Restore::where('status', 'queued')
            ->where(function ($w) use ($host) {
                $w->where('host_id', $host->id)
                    ->orWhereHas('host', function ($h) use ($host) {
                        $h->where('director_id', $host->director_id)
                            ->whereIn('connection_type', ['ftp', 'sftp', 'rsync', 'ssh']);
                    });
            })
            ->orderBy('id')
            ->with('run.job.repository', 'host')
            ->first();

        if (! $restore) {
            return response()->json(['restore' => null]);
        }

        $restore->forceFill(['status' => 'running'])->save();

        return response()->json(['restore' => [
            'id' => (string) $restore->id,
            'snapshot_id' => $restore->snapshot_id,
            'target_path' => $restore->target_path,
            'paths' => $restore->paths ?: [],
            'repository' => $this->repoPayload($restore->run?->job?->repository),
        ]]);
    }

    public function restoreReport(Request $request, Restore $restore)
    {
        $data = $request->validate([
            'status' => ['required', 'in:running,success,failed'],
            'log' => ['nullable', 'string'],
        ]);
        $restore->forceFill([
            'status' => $data['status'],
            'log' => $data['log'] ?? $restore->log,
        ])->save();

        return response()->noContent();
    }

    /** Store a snapshot's file listing (uploaded by the agent after a backup). */
    public function storeIndex(Request $request, Run $run)
    {
        $files = $request->input('files', []);
        if (! is_array($files)) {
            $files = [];
        }
        $run->forceFill(['file_index' => array_slice($files, 0, (int) config('backup.file_index_cap', 5000))])->save();

        return response()->noContent();
    }

    public function heartbeat(Request $request)
    {
        $host = $request->attributes->get('agent_host');
        $host->forceFill([
            'last_seen_at' => now(),
            'status' => 'online',
            'agent_version' => $request->input('agent_version', $host->agent_version),
        ])->save();

        $interval = (int) (Setting::get('agent_poll_interval') ?: 0);

        return response()->json([
            'update' => $this->updateOffer(),
            'poll_interval_seconds' => $interval > 0 ? $interval : null,
        ]);
    }

    /** Advertise a newer agent build when auto-update is enabled and configured. */
    private function updateOffer(): ?array
    {
        $s = Setting::map();
        if (($s['agent_auto_update'] ?? '0') !== '1') {
            return null;
        }
        $version = trim($s['agent_latest_version'] ?? '');
        $url = trim($s['agent_download_url'] ?? '');
        if ($version === '' || $url === '') {
            return null;
        }

        return ['version' => $version, 'url' => $url];
    }

    /** Hand the gateway the next due sync folder for its Director. */
    public function syncPoll(Request $request)
    {
        $host = $request->attributes->get('agent_host');

        $folder = SyncFolder::where('director_id', $host->director_id)
            ->where('enabled', true)
            ->where('status', '!=', 'running')
            ->where(function ($w) {
                $w->whereNull('last_synced_at')
                    ->orWhereRaw('DATE_ADD(last_synced_at, INTERVAL interval_minutes MINUTE) <= NOW()');
            })
            ->orderByRaw('last_synced_at IS NOT NULL, last_synced_at')
            ->with('sourceHost')
            ->first();

        if (! $folder) {
            return response()->json(['sync' => null]);
        }

        $folder->forceFill(['status' => 'running'])->save();

        $targets = [];
        foreach ($folder->targets ?? [] as $t) {
            $th = Host::find($t['host_id'] ?? null);
            if (! $th) {
                continue;
            }
            $targets[] = $this->syncHostPayload($th, $t['path'] ?? '', $host->id);
        }

        return response()->json(['sync' => [
            'id' => (string) $folder->id,
            'name' => $folder->name,
            'delete_extra' => (bool) $folder->delete_extra,
            'source' => $this->syncHostPayload($folder->sourceHost, $folder->source_path, $host->id),
            'targets' => $targets,
        ]]);
    }

    public function syncReport(Request $request, SyncFolder $syncFolder)
    {
        $data = $request->validate([
            'status' => ['required', 'in:success,failed'],
            'result' => ['nullable', 'string', 'max:4000'],
        ]);

        $syncFolder->forceFill([
            'status' => $data['status'],
            'last_synced_at' => now(),
            'last_result' => $data['result'] ?? null,
        ])->save();

        return response()->noContent();
    }

    /** Describe one endpoint of a sync (source or target) for the gateway. */
    private function syncHostPayload($h, string $path, int $gatewayHostId): array
    {
        // An agent-type host that is the executing gateway (or another node in
        // the same single-box Director) is written to locally.
        $local = ! $h || $h->connection_type === 'agent';

        return [
            'host_id' => (string) ($h->id ?? ''),
            'connector' => $local ? 'local' : $h->connection_type,
            'path' => $path,
            'is_gateway' => $h && $h->id === $gatewayHostId,
            'transport' => $this->transportPayload($h),
        ];
    }

    private function repoPayload($repo): ?array
    {
        if (! $repo) {
            return null;
        }
        $c = $repo->config ?? [];

        return [
            'backend' => $repo->backend,
            'filesystem_path' => $c['path'] ?? null,
            's3_endpoint' => $c['endpoint'] ?? null,
            'region' => $c['region'] ?? null,
            'bucket' => $c['bucket'] ?? null,
            'prefix' => $c['prefix'] ?? null,
            'access_key_id' => $repo->access_key_id,
            'secret_access_key' => $repo->secret_access_key,
            'password' => $repo->password,
            'compression' => $repo->compression,
        ];
    }

    /** Connection details for an agentless host, sent to the gateway agent. */
    private function transportPayload($h): ?array
    {
        if (! $h || $h->connection_type === 'agent') {
            return null;
        }

        return [
            'type' => $h->connection_type,
            'host' => $h->ip_address ?: $h->hostname,
            'port' => $h->port ? (string) $h->port : '',
            'username' => $h->username,
            'secret' => $h->secret,          // decrypted by the model cast
            'private_key' => $h->private_key, // decrypted by the model cast
        ];
    }

    private function retentionPayload($p): array
    {
        return [
            'keep_latest' => $p->keep_latest ?? 0,
            'keep_hourly' => $p->keep_hourly ?? 0,
            'keep_daily' => $p->keep_daily ?? 0,
            'keep_weekly' => $p->keep_weekly ?? 0,
            'keep_monthly' => $p->keep_monthly ?? 0,
            'keep_annual' => $p->keep_annual ?? 0,
        ];
    }
}
