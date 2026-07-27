<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Host;
use App\Models\Restore;
use App\Models\Run;
use App\Models\Setting;
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
                                ->whereIn('connection_type', ['ftp', 'sftp', 'rsync', 'ssh', 'multiftp', 'ingest']);
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
            // Stable kopia source identity for this job, so every run lands in
            // one source with real history that retention can expire against.
            'override_source' => $this->overrideSource($job),
            'repository' => $this->repoPayload($job->repository),
            'source' => $this->sourcePayload($job),
            'transport' => $this->transportPayload($job->host),
            'retention' => $this->retentionPayload($job->retentionPolicy),
        ]]);
    }

    /**
     * Ensure the authenticated agent is entitled to act on this run: either it
     * is the run's own host, or it is the gateway agent for an agentless host in
     * the same Director. Mirrors the claim scope in poll().
     */
    private function authorizeRunForAgent(Request $request, Run $run): void
    {
        $host = $request->attributes->get('agent_host');
        $rh = $run->loadMissing('job.host')->job?->host;
        abort_unless(
            $rh && ($rh->id === $host->id
                || ($rh->director_id === $host->director_id
                    && in_array($rh->connection_type, ['ftp', 'sftp', 'rsync', 'ssh', 'multiftp', 'ingest'], true))),
            403
        );
    }

    /** Record progress or the final result of a run. */
    public function report(Request $request, Run $run)
    {
        $this->authorizeRunForAgent($request, $run);
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

        // Agentless hosts don't poll, so a run reaching one is our signal that
        // it's online (keeps the host from showing a misleading "offline").
        if (in_array($data['status'], ['running', 'success', 'warn'], true)) {
            $host = $run->job?->host;
            if ($host && $host->connection_type !== 'agent') {
                $host->forceFill(['status' => 'online', 'last_seen_at' => now()])->save();
            }
        }

        if ($data['status'] === 'failed') {
            $this->notifyFailure($run);
        }

        // A finished backup changed how full the repository disk is. Re-read the
        // local node's disks now so Storage Devices is current without anyone
        // clicking Detect Disks.
        if (in_array($data['status'], ['success', 'warn'], true)) {
            $director = $run->loadMissing('job.host.director')->job?->host?->director;
            if ($director) {
                app(\App\Services\DiskInventory::class)->refreshIfStale($director, 30);
            }
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
        $host = $request->attributes->get('agent_host');
        $rh = $restore->loadMissing('host')->host;
        abort_unless(
            $rh && ($rh->id === $host->id
                || ($rh->director_id === $host->director_id
                    && in_array($rh->connection_type, ['ftp', 'sftp', 'rsync', 'ssh'], true))),
            403
        );
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
        $this->authorizeRunForAgent($request, $run);
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
            'license' => $this->licenseBlob(),
            'ingest' => $this->ingestConfigs($host),
        ]);
    }

    /**
     * Ingest (receive) connections this gateway agent should serve: every
     * ingest host in the same Director, shaped for the agent's SFTP server.
     * Only SFTP is functional today (FTP/S3 are scaffolded but not served).
     */
    private function ingestConfigs($host): array
    {
        return Host::where('director_id', $host->director_id)
            ->where('connection_type', 'ingest')
            ->get()
            ->map(fn ($h) => $h->ingestConfigForAgent())
            ->filter()
            ->values()
            ->all();
    }

    /**
     * The scriptgain-signed license (canonical payload + signature) for agents
     * to re-verify offline against the embedded vendor key. Null until the
     * install has completed at least one signed validation with scriptgain.
     */
    private function licenseBlob(): ?array
    {
        $raw = Setting::get('license_signed');
        if (! $raw) {
            return null;
        }
        $blob = json_decode($raw, true);

        return (is_array($blob) && isset($blob['canonical'], $blob['signature'])) ? $blob : null;
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
        $sha256 = strtolower(trim($s['agent_download_sha256'] ?? ''));
        $signature = trim($s['agent_download_signature'] ?? '');
        // The agent refuses any offer without a checksum + vendor signature (and a
        // non-https URL), so advertising a half-configured update just wastes a
        // download. Withhold the offer until all four fields are set.
        if ($version === '' || $url === '' || $sha256 === '' || $signature === '') {
            return null;
        }

        return [
            'version' => $version,
            'url' => $url,
            'sha256' => $sha256,
            'signature' => $signature,
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

    /**
     * The job's source payload for the agent. For a multi-FTP host the accounts
     * (with credentials) live on the host, encrypted — inject them decrypted at
     * poll time so the gateway can fan out to every account in one snapshot.
     */
    private function sourcePayload($job)
    {
        if ($job->type === 'multiftp') {
            return ['accounts' => $job->host?->ftpAccountsForAgent() ?? []];
        }

        // Ingest snapshot: always snapshot the host's *current* drop folder, so
        // editing the folder on the host takes effect without touching the job.
        if ($job->connector === 'ingest') {
            return ['root' => $job->host?->ingest_folder, 'excludes' => []];
        }

        return $job->source ?: new \stdClass;
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

    /**
     * The stable kopia source identity for a job, as "job-<id>@<host>:<path>".
     *
     * Agentless pulls and database dumps materialize into a fresh temp spool
     * every run, so without this each night's snapshot became its OWN kopia
     * source holding exactly one snapshot: retention had nothing to expire and
     * repositories grew without bound (43 one-snapshot sources on the first
     * install to hit it). Overriding the recorded source pins every run of a job
     * to one identity, which is what makes keep-daily/weekly/monthly mean
     * anything.
     *
     * Returns null for a plain local files job, whose real path is already
     * stable: changing its identity would orphan the history it already has.
     */
    private function overrideSource($job): ?string
    {
        if ($job->connector === 'agent' && $job->type === 'files') {
            return null;
        }

        $host = $job->host;
        $hostLabel = $host?->hostname ?: ($host?->ip_address ?: 'director');
        // kopia parses user@host:path, so the host part must not contain @ or :.
        $hostLabel = str_replace(['@', ':', ' '], '-', $hostLabel);

        return "job-{$job->id}@{$hostLabel}:" . $this->logicalPath($job);
    }

    /** The path a job's snapshots should be recorded under. */
    private function logicalPath($job): string
    {
        if ($job->connector === 'ingest') {
            return $job->host?->ingest_folder ?: '/ingest';
        }
        if ($job->type !== 'files') {
            return '/' . $job->type;   // /mysql, /postgres, /composite, /multiftp
        }

        $src = is_array($job->source) ? $job->source : [];
        if (! empty($src['root'])) {
            return $src['root'];
        }
        // Multi-path pulls use rsync -R, so the spool mirrors the absolute
        // layout of the source host: the snapshot root really is /.
        return '/';
    }

    /** Return the next queued maintenance task for this agent, or {task:null}. */
    public function maintenancePoll(Request $request)
    {
        $host = $request->attributes->get('agent_host');

        $task = \App\Models\MaintenanceTask::where('status', 'queued')
            ->where('host_id', $host->id)
            ->orderBy('id')
            ->with('repository')
            ->first();

        if (! $task || ! $task->repository) {
            return response()->json(['task' => null]);
        }

        $task->forceFill(['status' => 'running', 'started_at' => now()])->save();

        // An operator-driven delete rides the prune path: the agent only acts on
        // delete_snapshots when the task prunes. Sending no sources with it keeps
        // retention out of it, so exactly the named snapshots go and nothing else.
        $named = $task->deletesNamedSnapshots();

        return response()->json(['task' => [
            'id' => (string) $task->id,
            'kind' => $task->kind,
            'prune' => $task->prunes() || $named,
            'maintenance' => $task->maintains(),
            'repository' => $this->repoPayload($task->repository),
            // Retention is per job, so a repository-wide prune has to walk every
            // job that writes into it and expire that job's own source.
            'sources' => $task->prunes() ? $this->maintenanceSources($task->repository) : [],
            // Snapshots the master has determined fall outside retention, by id.
            // Policy-based expiry can only act on snapshots kopia groups under one
            // source; this covers the rest (see RetentionPlanner).
            'delete_snapshots' => match (true) {
                $named => $task->snapshotIds(),
                $task->prunes() => $this->expiredSnapshotIds($task->repository),
                default => [],
            },
        ]]);
    }

    /** Each enabled job in a repository, with its source identity + retention. */
    private function maintenanceSources($repository): array
    {
        return $repository->jobs()
            ->where('enabled', true)
            ->with('retentionPolicy', 'host')
            ->get()
            ->map(fn ($job) => [
                'job_id' => (string) $job->id,
                'name' => $job->name,
                // Null override means the job snapshots a real local path, which
                // the agent already knows how to address.
                'source' => $this->overrideSource($job) ?? $this->logicalPath($job),
                'retention' => $this->retentionPayload($job->retentionPolicy),
            ])
            ->all();
    }

    /**
     * Every snapshot in this repository that its job's retention policy no longer
     * keeps, planned from the master's own run history.
     */
    private function expiredSnapshotIds($repository): array
    {
        $planner = app(\App\Services\RetentionPlanner::class);
        $ids = [];

        foreach ($repository->jobs()->with('retentionPolicy')->get() as $job) {
            $runs = Run::where('backup_job_id', $job->id)->restorable()->get();
            $plan = $planner->plan($runs, $job->retentionPolicy);
            $ids = array_merge($ids, $plan['expire']);
        }

        return array_values(array_unique($ids));
    }

    /** Record progress or the final result of a maintenance task. */
    public function maintenanceReport(Request $request, \App\Models\MaintenanceTask $maintenanceTask)
    {
        $host = $request->attributes->get('agent_host');
        abort_unless($maintenanceTask->host_id === $host->id, 403);

        $data = $request->validate([
            'status' => ['required', 'in:running,success,failed'],
            'log' => ['nullable', 'string'],
            // Snapshot ids still present in the repository after the pass. Used
            // to retire runs whose restore point no longer exists.
            'snapshots' => ['nullable', 'array'],
            'snapshots.*' => ['string'],
        ]);

        $update = ['status' => $data['status'], 'log' => $data['log'] ?? $maintenanceTask->log];
        if (in_array($data['status'], ['success', 'failed'], true)) {
            $update['finished_at'] = now();
        }
        if ($data['status'] === 'failed') {
            $update['error'] = $data['log'] ?? 'Maintenance task failed.';
        }
        $maintenanceTask->forceFill($update)->save();

        if ($data['status'] === 'success') {
            if (is_array($data['snapshots'] ?? null)) {
                $this->retireExpiredSnapshots($maintenanceTask, $data['snapshots']);
            }
            // Reclaimed space is the point of this task: re-read the disks so the
            // Storage page reflects it without anyone clicking Detect Disks.
            $director = $maintenanceTask->repository?->director;
            if ($director) {
                app(\App\Services\DiskInventory::class)->refresh($director);
            }
        }

        return response()->noContent();
    }

    /**
     * Flag runs in this repository whose snapshot the prune deleted, so the
     * Snapshots list stops offering restore points that are gone.
     */
    private function retireExpiredSnapshots(\App\Models\MaintenanceTask $task, array $surviving): void
    {
        $jobIds = $task->repository->jobs()->pluck('id');
        if ($jobIds->isEmpty()) {
            return;
        }

        Run::whereIn('backup_job_id', $jobIds)
            ->whereNotNull('snapshot_id')
            ->whereNull('snapshot_expired_at')
            ->whereNotIn('snapshot_id', $surviving ?: ['\0'])
            ->update(['snapshot_expired_at' => now()]);
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
