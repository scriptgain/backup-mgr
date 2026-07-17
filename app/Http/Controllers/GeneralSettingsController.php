<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GeneralSettingsController extends Controller
{
    /** Defaults for every General setting. Keys are the Setting table keys. */
    public static function defaults(): array
    {
        return [
            // Regional & display
            'timezone' => config('app.timezone', 'UTC'),
            'date_format' => 'M j, Y',
            'time_format' => 'g:i A',
            'week_starts_on' => 'sunday',
            'rows_per_page' => '25',
            // Backup defaults
            'default_compression' => 'zstd',
            'default_keep_latest' => '10',
            'prune_after_backup' => '0',
            'verify_after_backup' => '0',
            'max_concurrent_jobs' => '2',
            // Agents
            'agent_poll_interval' => '30',
            'offline_after_minutes' => '5',
            'agent_auto_update' => '0',
            'agent_latest_version' => '',
            'agent_download_url' => '',
            'agent_download_sha256' => '',
            'agent_download_signature' => '',
            // Maintenance & housekeeping
            'auto_maintenance' => '1',
            'run_history_days' => '90',
            'audit_log_days' => '180',
            'file_index_cap' => '5000',
            // Security
            'session_timeout_minutes' => '120',
            'require_2fa' => '0',
            'force_password_days' => '0',
        ];
    }

    public function edit()
    {
        $map = Setting::map();
        $v = [];
        foreach (static::defaults() as $key => $default) {
            $v[$key] = $map[$key] ?? $default;
        }

        return view('settings.general', [
            'v' => $v,
            'timezones' => \DateTimeZone::listIdentifiers(),
            'now' => now(),
            'info' => [
                'Product' => config('brand.name', 'BackupMGR'),
                'App Version' => config('app.version', '1.0.0'),
                'PHP' => PHP_VERSION,
                'Laravel' => app()->version(),
                'Database' => config('database.default'),
                'Environment' => app()->environment(),
                'Server Time' => now()->format('D, M j Y g:i A T'),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'timezone' => ['required', 'string', Rule::in(\DateTimeZone::listIdentifiers())],
            'date_format' => ['required', 'string', 'max:20'],
            'time_format' => ['required', 'string', 'max:20'],
            'week_starts_on' => ['required', Rule::in(['sunday', 'monday'])],
            'rows_per_page' => ['required', 'integer', 'min:10', 'max:200'],
            'default_compression' => ['required', Rule::in(['zstd', 's2', 'none'])],
            'default_keep_latest' => ['required', 'integer', 'min:1', 'max:1000'],
            'max_concurrent_jobs' => ['required', 'integer', 'min:1', 'max:50'],
            'agent_poll_interval' => ['required', 'integer', 'min:5', 'max:3600'],
            'offline_after_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'agent_latest_version' => ['nullable', 'string', 'max:40'],
            'agent_download_url' => ['nullable', 'url', 'max:500'],
            'agent_download_sha256' => ['nullable', 'string', 'regex:/^[0-9a-fA-F]{64}$/'],
            'agent_download_signature' => ['nullable', 'string', 'max:1024'],
            'run_history_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'audit_log_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'file_index_cap' => ['required', 'integer', 'min:100', 'max:100000'],
            'session_timeout_minutes' => ['required', 'integer', 'min:5', 'max:43200'],
            'force_password_days' => ['required', 'integer', 'min:0', 'max:3650'],
        ]);

        // Toggles submit "0"/"1" via hidden input; normalize explicitly.
        foreach (['prune_after_backup', 'verify_after_backup', 'agent_auto_update', 'auto_maintenance', 'require_2fa'] as $t) {
            $data[$t] = $request->boolean($t) ? '1' : '0';
        }

        foreach ($data as $key => $value) {
            Setting::put($key, (string) $value);
        }

        AuditLog::record('updated', 'General settings updated');

        return back()->with('status', 'General settings saved.');
    }
}
