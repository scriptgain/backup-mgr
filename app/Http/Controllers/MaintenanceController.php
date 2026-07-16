<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
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

        return view('settings.maintenance', [
            'v' => $v,
            'days' => self::DAYS,
            'selectedDays' => $selectedDays,
            'allowedNow' => static::allowedNow($v),
            'repositoryCount' => Repository::count(),
            'now' => now(),
        ]);
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
