<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BackupJob;
use App\Models\Run;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JobController extends Controller
{
    private function guard(BackupJob $job): void
    {
        abort_unless($job->host?->isVisibleTo(auth()->user()), 403);
    }

    public function index(Request $request)
    {
        return BackupJob::whereHas('host', fn ($q) => $q->visibleTo(auth()->user()))
            ->when($request->integer('host_id'), fn ($q, $id) => $q->where('host_id', $id))
            ->with('host:id,name')
            ->latest()
            ->paginate(50);
    }

    public function store(Request $request)
    {
        $data = $this->validateJob($request);
        // The job must attach to a host the caller can see.
        $host = \App\Models\Host::findOrFail($data['host_id']);
        abort_unless($host->isVisibleTo(auth()->user()), 403);

        return response()->json(BackupJob::create($data), 201);
    }

    public function show(BackupJob $job)
    {
        $this->guard($job);

        return $job->load('host:id,name', 'repository:id,name', 'retentionPolicy');
    }

    public function update(Request $request, BackupJob $job)
    {
        $this->guard($job);
        $data = $this->validateJob($request, updating: true);
        // Prevent moving a job onto a host the caller cannot see.
        if (! empty($data['host_id'])) {
            $target = \App\Models\Host::findOrFail($data['host_id']);
            abort_unless($target->isVisibleTo(auth()->user()), 403);
        }
        $job->update($data);

        return $job;
    }

    public function destroy(BackupJob $job)
    {
        $this->guard($job);
        $job->delete();

        return response()->noContent();
    }

    /** Queue a run now. The director/agent picks it up on its next poll. */
    public function run(BackupJob $job)
    {
        $this->guard($job);
        $run = Run::create([
            'backup_job_id' => $job->id,
            'status' => 'queued',
        ]);

        return response()->json($run, 202);
    }

    private function validateJob(Request $request, bool $updating = false): array
    {
        $req = $updating ? 'sometimes' : 'required';

        return $request->validate([
            'host_id' => [$req, Rule::exists('hosts', 'id')],
            'repository_id' => ['nullable', Rule::exists('repositories', 'id')],
            'retention_policy_id' => ['nullable', Rule::exists('retention_policies', 'id')],
            'name' => [$req, 'string', 'max:120'],
            'type' => ['sometimes', Rule::in(['files', 'mysql', 'postgres', 'composite'])],
            'connector' => ['sometimes', Rule::in(['agent', 'ssh', 'sftp', 'ftp', 'rsync', 's3'])],
            'source' => ['nullable', 'array'],
            'schedule_cron' => ['nullable', 'string', 'max:120'],
            'enabled' => ['boolean'],
            'prune_after_backup' => ['boolean'],
            'prune_schedule_cron' => ['nullable', 'string', 'max:120'],
            'pre_hook' => ['nullable', 'string'],
            'post_hook' => ['nullable', 'string'],
        ]);
    }
}
