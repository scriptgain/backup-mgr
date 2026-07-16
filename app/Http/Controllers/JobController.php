<?php

namespace App\Http\Controllers;

use App\Models\BackupJob;
use App\Models\Host;
use App\Models\Repository;
use App\Models\RetentionPolicy;
use App\Models\Run;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JobController extends Controller
{
    public function index()
    {
        $jobs = BackupJob::where('ad_hoc', false)
            ->whereHas('host', fn ($q) => $q->visibleTo(auth()->user()))
            ->with('host.director', 'repository')->latest()->get();

        return view('jobs.index', compact('jobs'));
    }

    private function guard(BackupJob $job): void
    {
        abort_unless($job->host?->isVisibleTo(auth()->user()), 403);
    }

    /** Repositories usable by this user: global, or under a director they own or host a visible host of. */
    private function visibleRepositories($user)
    {
        return Repository::where(function ($q) use ($user) {
            $q->whereNull('director_id')
                ->orWhereHas('director', fn ($d) => $d->visibleTo($user))
                ->orWhereHas('director.hosts', fn ($h) => $h->visibleTo($user));
        })->orderBy('name')->get();
    }

    public function create(Request $request)
    {
        $user = auth()->user();
        $hosts = Host::visibleTo($user)->with('director:id,name')->orderBy('name')->get();
        $repositories = $this->visibleRepositories($user);
        $scheduleTemplates = \App\Models\ScheduleTemplate::orderBy('name')->get();
        $selectedHost = $request->integer('host');

        return view('jobs.create', compact('hosts', 'repositories', 'scheduleTemplates', 'selectedHost'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'host_id' => ['required', Rule::exists('hosts', 'id')],
            'repository_id' => ['required', Rule::exists('repositories', 'id')],
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(['files', 'mysql', 'postgres', 'composite', 'diskimage'])],
            'path' => ['nullable', 'string', 'max:1024'],
            'excludes' => ['nullable', 'array'],
            'excludes.*' => ['nullable', 'string', 'max:1024'],
            'devices' => ['nullable', 'array'],
            'devices.*' => ['nullable', 'string', 'max:255'],
            'database' => ['nullable', 'string', 'max:120'],
            'db_user' => ['nullable', 'string', 'max:120'],
            'db_password' => ['nullable', 'string'],
            'composite_paths' => ['nullable', 'array'],
            'composite_paths.*.label' => ['nullable', 'string', 'max:120'],
            'composite_paths.*.root' => ['nullable', 'string', 'max:1024'],
            'composite_dbs' => ['nullable', 'array'],
            'composite_dbs.*.label' => ['nullable', 'string', 'max:120'],
            'composite_dbs.*.engine' => ['nullable', Rule::in(['mysql', 'postgres'])],
            'composite_dbs.*.database' => ['nullable', 'string', 'max:120'],
            'composite_dbs.*.user' => ['nullable', 'string', 'max:120'],
            'composite_dbs.*.password' => ['nullable', 'string'],
            'composite_excludes' => ['nullable', 'array'],
            'composite_excludes.*' => ['nullable', 'string', 'max:1024'],
            'schedule_cron' => ['nullable', 'string', 'max:120'],
            'enabled' => ['boolean'],
            'prune_after_backup' => ['boolean'],
            'prune_schedule_cron' => ['nullable', 'string', 'max:120'],
            'keep_latest' => ['integer', 'min:0'],
            'keep_daily' => ['integer', 'min:0'],
            'keep_weekly' => ['integer', 'min:0'],
            'keep_monthly' => ['integer', 'min:0'],
        ]);

        $host = Host::findOrFail($data['host_id']);
        abort_unless($host->isVisibleTo(auth()->user()), 403);

        $policy = RetentionPolicy::create([
            'name' => $data['name'] . ' retention',
            'keep_latest' => $data['keep_latest'] ?? 0,
            'keep_daily' => $data['keep_daily'] ?? 7,
            'keep_weekly' => $data['keep_weekly'] ?? 4,
            'keep_monthly' => $data['keep_monthly'] ?? 6,
        ]);

        $job = BackupJob::create([
            'host_id' => $host->id,
            'repository_id' => $data['repository_id'],
            'retention_policy_id' => $policy->id,
            'name' => $data['name'],
            'type' => $data['type'],
            'connector' => $host->connection_type,
            'source' => $this->buildSource($data),
            'schedule_cron' => ($data['schedule_cron'] ?? null) ?: null,
            'enabled' => $request->boolean('enabled'),
            'prune_after_backup' => $request->boolean('prune_after_backup'),
            'prune_schedule_cron' => ($data['prune_schedule_cron'] ?? null) ?: null,
        ]);

        return redirect()->route('jobs.show', $job)->with('status', "Job \"{$job->name}\" created.");
    }

    public function show(BackupJob $job)
    {
        $job->load('host.director', 'repository', 'retentionPolicy', 'runs');
        $this->guard($job);

        return view('jobs.show', compact('job'));
    }

    public function edit(BackupJob $job)
    {
        $job->load('host.director', 'retentionPolicy');
        $this->guard($job);
        $user = auth()->user();
        $hosts = Host::visibleTo($user)->with('director:id,name')->orderBy('name')->get();
        $repositories = $this->visibleRepositories($user);
        $scheduleTemplates = \App\Models\ScheduleTemplate::orderBy('name')->get();

        return view('jobs.edit', compact('job', 'hosts', 'repositories', 'scheduleTemplates'));
    }

    public function update(Request $request, BackupJob $job)
    {
        $job->loadMissing('host.director');
        $this->guard($job);
        $data = $request->validate([
            'host_id' => ['required', Rule::exists('hosts', 'id')],
            'repository_id' => ['required', Rule::exists('repositories', 'id')],
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(['files', 'mysql', 'postgres', 'composite', 'diskimage'])],
            'path' => ['nullable', 'string', 'max:1024'],
            'excludes' => ['nullable', 'array'],
            'excludes.*' => ['nullable', 'string', 'max:1024'],
            'devices' => ['nullable', 'array'],
            'devices.*' => ['nullable', 'string', 'max:255'],
            'database' => ['nullable', 'string', 'max:120'],
            'db_user' => ['nullable', 'string', 'max:120'],
            'db_password' => ['nullable', 'string'],
            'composite_paths' => ['nullable', 'array'],
            'composite_paths.*.label' => ['nullable', 'string', 'max:120'],
            'composite_paths.*.root' => ['nullable', 'string', 'max:1024'],
            'composite_dbs' => ['nullable', 'array'],
            'composite_dbs.*.label' => ['nullable', 'string', 'max:120'],
            'composite_dbs.*.engine' => ['nullable', Rule::in(['mysql', 'postgres'])],
            'composite_dbs.*.database' => ['nullable', 'string', 'max:120'],
            'composite_dbs.*.user' => ['nullable', 'string', 'max:120'],
            'composite_dbs.*.password' => ['nullable', 'string'],
            'composite_excludes' => ['nullable', 'array'],
            'composite_excludes.*' => ['nullable', 'string', 'max:1024'],
            'schedule_cron' => ['nullable', 'string', 'max:120'],
            'enabled' => ['boolean'],
            'prune_after_backup' => ['boolean'],
            'prune_schedule_cron' => ['nullable', 'string', 'max:120'],
            'keep_latest' => ['integer', 'min:0'],
            'keep_daily' => ['integer', 'min:0'],
            'keep_weekly' => ['integer', 'min:0'],
            'keep_monthly' => ['integer', 'min:0'],
        ]);

        $host = Host::findOrFail($data['host_id']);
        abort_unless($host->isVisibleTo(auth()->user()), 403);

        $policy = $job->retentionPolicy ?: new RetentionPolicy(['name' => $data['name'] . ' retention']);
        $policy->fill([
            'keep_latest' => $data['keep_latest'] ?? 0,
            'keep_daily' => $data['keep_daily'] ?? 0,
            'keep_weekly' => $data['keep_weekly'] ?? 0,
            'keep_monthly' => $data['keep_monthly'] ?? 0,
        ])->save();

        // For DB jobs, keep the stored password when left blank on edit.
        if (in_array($data['type'], ['mysql', 'postgres']) && empty($data['db_password'])) {
            $data['db_password'] = $job->source['password'] ?? null;
        }

        // Composite: reuse each database's stored password when left blank on edit,
        // matched by database name against the existing source.
        if ($data['type'] === 'composite') {
            $existing = collect($job->source['databases'] ?? [])->keyBy('database');
            $data['composite_dbs'] = collect($data['composite_dbs'] ?? [])->map(function ($d) use ($existing) {
                if (empty($d['password'] ?? null) && filled($d['database'] ?? null) && $existing->has($d['database'])) {
                    $d['password'] = $existing[$d['database']]['password'] ?? '';
                }

                return $d;
            })->all();
        }

        $job->update([
            'host_id' => $host->id,
            'repository_id' => $data['repository_id'],
            'retention_policy_id' => $policy->id,
            'name' => $data['name'],
            'type' => $data['type'],
            'connector' => $host->connection_type,
            'source' => $this->buildSource($data),
            'schedule_cron' => ($data['schedule_cron'] ?? null) ?: null,
            'enabled' => $request->boolean('enabled'),
            'prune_after_backup' => $request->boolean('prune_after_backup'),
            'prune_schedule_cron' => ($data['prune_schedule_cron'] ?? null) ?: null,
        ]);

        return redirect()->route('jobs.show', $job)->with('status', "Job \"{$job->name}\" updated.");
    }

    public function destroy(BackupJob $job)
    {
        $job->loadMissing('host.director');
        $this->guard($job);
        $name = $job->name;
        $job->delete();

        return redirect()->route('jobs.index')->with('status', "Job \"{$name}\" deleted.");
    }

    /** Queue a run now; the director/agent picks it up on next poll. */
    public function run(BackupJob $job)
    {
        $job->loadMissing('host.director');
        $this->guard($job);
        Run::create(['backup_job_id' => $job->id, 'status' => 'queued']);
        \App\Models\AuditLog::record('backup', 'Backup queued for job "'.$job->name.'"', $job);

        return redirect()->route('jobs.show', $job)->with('status', 'Backup queued. It will run on the next agent poll.');
    }

    private function buildSource(array $data): array
    {
        return match ($data['type']) {
            'files' => [
                'root' => $data['path'] ?? '',
                'excludes' => array_values(array_filter($data['excludes'] ?? [], fn ($e) => filled($e))),
            ],
            'diskimage' => [
                'devices' => collect($data['devices'] ?? [])
                    ->map(fn ($d) => trim((string) $d))
                    ->filter(fn ($d) => $d !== '')
                    ->values()->all(),
            ],
            'mysql', 'postgres' => array_filter([
                'database' => $data['database'] ?? null,
                'user' => $data['db_user'] ?? null,
                'password' => $data['db_password'] ?? null,
            ], fn ($v) => $v !== null && $v !== ''),
            'composite' => [
                'excludes' => array_values(array_filter($data['composite_excludes'] ?? [], fn ($e) => filled($e))),
                'paths' => collect($data['composite_paths'] ?? [])
                    ->filter(fn ($p) => filled($p['root'] ?? null))
                    ->map(fn ($p) => ['label' => $p['label'] ?? '', 'root' => $p['root']])
                    ->values()->all(),
                'databases' => collect($data['composite_dbs'] ?? [])
                    ->filter(fn ($d) => filled($d['database'] ?? null))
                    ->map(fn ($d) => [
                        'label' => $d['label'] ?? '',
                        'engine' => $d['engine'] ?? 'mysql',
                        'database' => $d['database'],
                        'user' => $d['user'] ?? '',
                        'password' => $d['password'] ?? '',
                    ])->values()->all(),
            ],
            default => [],
        };
    }
}
