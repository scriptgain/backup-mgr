<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Host;
use App\Models\MaintenanceTask;
use App\Models\Repository;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MaintenanceController extends Controller
{
    /** Ordered day-of-week tokens matching Carbon's lowercase `D` format. */
    public const DAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    /** Defaults for every Maintenance setting. Keys are Setting table keys. */
    public static function defaults(): array
    {
        return [
            // Automatic kopia maintenance (compaction + GC) after backups.
            'auto_maintenance' => '1',
            // Restrict maintenance to a nightly window so it never competes
            // with production traffic. When disabled, maintenance may run after
            // any backup, any time.
            'maintenance_window_enabled' => '0',
            'maintenance_window_start' => '02:00',
            'maintenance_window_end' => '05:00',
            'maintenance_days' => implode(',', self::DAYS),
            // Global pruning: force retention + space reclaim after every
            // backup on every job, overriding the per-job toggle.
            'prune_all_jobs' => '0',
        ];
    }

    /**
     * Decide whether kopia maintenance may run right now, honoring the master's
     * configured window. Stateless: evaluated against the current time in the
     * app timezone each time an agent polls. Consumed by AgentController::poll.
     */
    public static function allowedNow(array $s, ?\DateTimeInterface $now = null): bool
    {
        if (($s['auto_maintenance'] ?? '1') !== '1') {
            return false;
        }
        if (($s['maintenance_window_enabled'] ?? '0') !== '1') {
            return true;
        }

        $now = $now ? \Illuminate\Support\Carbon::instance($now) : now();

        $days = array_filter(explode(',', $s['maintenance_days'] ?? ''));
        if ($days && ! in_array(strtolower($now->format('D')), $days, true)) {
            return false;
        }

        $start = $s['maintenance_window_start'] ?? '00:00';
        $end = $s['maintenance_window_end'] ?? '23:59';
        $cur = $now->format('H:i');

        // A window like 22:00–05:00 wraps past midnight.
        return $start <= $end
            ? ($cur >= $start && $cur <= $end)
            : ($cur >= $start || $cur <= $end);
    }

    public function edit()
    {
        $map = Setting::map();
        $v = [];
        foreach (static::defaults() as $key => $default) {
            $v[$key] = $map[$key] ?? $default;
        }

        $selectedDays = array_filter(explode(',', $v['maintenance_days']));

        // Everything the Run Now table needs: which agent will execute a task for
        // each repository, and any pass already in flight.
        $repositories = Repository::with('director.gatewayAgent', 'activeMaintenanceTask')
            ->withCount(['jobs' => fn ($q) => $q->where('enabled', true)])
            ->orderBy('name')
            ->get();

        return view('settings.maintenance', [
            'v' => $v,
            'days' => self::DAYS,
            'selectedDays' => $selectedDays,
            'allowedNow' => static::allowedNow($v),
            'repositoryCount' => Repository::count(),
            'repositories' => $repositories,
            'tasks' => MaintenanceTask::with('repository', 'host', 'user')->latest()->limit(10)->get(),
            'minAgentVersion' => Host::MIN_AGENT_VERSION_FOR_TASKS,
            'now' => now(),
        ]);
    }

    /** Queue a manual prune / maintenance pass for one repository. */
    public function run(Request $request)
    {
        $data = $request->validate([
            'repository_id' => ['required', 'exists:repositories,id'],
            'kind' => ['required', Rule::in(MaintenanceTask::KINDS)],
        ]);

        $repo = Repository::findOrFail($data['repository_id']);
        abort_unless(
            $request->user()->isAdmin() || $repo->director?->user_id === $request->user()->id,
            403
        );

        $gateway = $repo->director?->gatewayAgent;
        if (! $gateway) {
            return back()->with('error', "No agent host in Director \"{$repo->director?->name}\" can reach \"{$repo->name}\". Install the agent on that node first.");
        }

        // One at a time per repository: kopia takes a repo-wide maintenance lock,
        // so a second concurrent pass would just fail on the lock.
        $active = $repo->activeMaintenanceTask;
        if ($active) {
            return back()->with('error', "\"{$repo->name}\" already has a {$active->status} {$active->kindLabel()} task. Wait for it to finish.");
        }

        $task = MaintenanceTask::create([
            'repository_id' => $repo->id,
            'host_id' => $gateway->id,
            'user_id' => $request->user()->id,
            'kind' => $data['kind'],
            'status' => 'queued',
        ]);

        AuditLog::record('queued', "{$task->kindLabel()} queued for repository \"{$repo->name}\"", $task);

        $note = $gateway->supportsMaintenanceTasks()
            ? 'It starts on the next agent check-in; refresh for the result.'
            : "Warning: {$gateway->name} reports agent " . ($gateway->agent_version ?: 'unknown')
                . ', which cannot run manual tasks. Update it to ' . Host::MIN_AGENT_VERSION_FOR_TASKS . ' or newer.';

        return back()->with('status', "{$task->kindLabel()} queued for \"{$repo->name}\". {$note}");
    }

    /** Cancel a task the agent has not claimed yet. */
    public function cancel(Request $request, MaintenanceTask $maintenanceTask)
    {
        abort_unless(
            $request->user()->isAdmin() || $maintenanceTask->repository?->director?->user_id === $request->user()->id,
            403
        );

        if ($maintenanceTask->status !== 'queued') {
            return back()->with('error', 'Only a queued task can be cancelled; this one is already ' . $maintenanceTask->status . '.');
        }

        $maintenanceTask->forceFill([
            'status' => 'failed',
            'error' => 'Cancelled before the agent claimed it.',
            'finished_at' => now(),
        ])->save();

        AuditLog::record('cancelled', "{$maintenanceTask->kindLabel()} cancelled for repository \"{$maintenanceTask->repository?->name}\"", $maintenanceTask);

        return back()->with('status', 'Task cancelled.');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'maintenance_window_start' => ['required', 'date_format:H:i'],
            'maintenance_window_end' => ['required', 'date_format:H:i'],
            'maintenance_days' => ['nullable', 'array'],
            'maintenance_days.*' => [Rule::in(self::DAYS)],
        ]);

        // Toggles submit "0"/"1" via a hidden input; normalize explicitly.
        foreach (['auto_maintenance', 'maintenance_window_enabled', 'prune_all_jobs'] as $t) {
            Setting::put($t, $request->boolean($t) ? '1' : '0');
        }

        Setting::put('maintenance_window_start', $data['maintenance_window_start']);
        Setting::put('maintenance_window_end', $data['maintenance_window_end']);
        Setting::put('maintenance_days', implode(',', $data['maintenance_days'] ?? []));

        AuditLog::record('updated', 'Maintenance settings updated');

        return back()->with('status', 'Maintenance settings saved.');
    }
}
