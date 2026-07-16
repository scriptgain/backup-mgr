<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    /**
     * Apply DB-backed branding over config at boot (DB-driven config pattern).
     * Guarded so the app still boots before migrations run.
     */
    public function boot(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }
            $s = Setting::map();
            // DB-driven timezone: makes schedules fire and times display in the
            // configured zone (e.g. America/Phoenix) instead of UTC.
            if (! empty($s['timezone'])) {
                config(['app.timezone' => $s['timezone']]);
                date_default_timezone_set($s['timezone']);
            }
            if (! empty($s['brand_name'])) {
                config(['brand.name' => $s['brand_name'], 'app.name' => $s['brand_name']]);
            }
            if (! empty($s['brand_tagline'])) {
                config(['brand.tagline' => $s['brand_tagline']]);
            }
            if (! empty($s['brand_accent'])) {
                config(['brand.accent' => $s['brand_accent']]);
            }
            // Idle session timeout (minutes) -> Laravel session lifetime. Live.
            if (! empty($s['session_timeout_minutes'])) {
                config(['session.lifetime' => (int) $s['session_timeout_minutes']]);
            }
            // Expose General defaults app-wide so forms and agents can read them.
            config([
                'backup.date_format' => $s['date_format'] ?? 'M j, Y',
                'backup.time_format' => $s['time_format'] ?? 'g:i A',
                'backup.rows_per_page' => (int) ($s['rows_per_page'] ?? 25),
                'backup.default_compression' => $s['default_compression'] ?? 'zstd',
                'backup.default_keep_latest' => (int) ($s['default_keep_latest'] ?? 10),
                'backup.prune_after_backup' => ($s['prune_after_backup'] ?? '0') === '1',
                'backup.require_2fa' => ($s['require_2fa'] ?? '0') === '1',
                'backup.force_password_days' => (int) ($s['force_password_days'] ?? 0),
                'backup.run_history_days' => (int) ($s['run_history_days'] ?? 90),
                'backup.audit_log_days' => (int) ($s['audit_log_days'] ?? 180),
                'backup.agent_auto_update' => ($s['agent_auto_update'] ?? '0') === '1',
                'backup.max_concurrent_jobs' => (int) ($s['max_concurrent_jobs'] ?? 2),
                'backup.agent_poll_interval' => (int) ($s['agent_poll_interval'] ?? 30),
                'backup.offline_after_minutes' => (int) ($s['offline_after_minutes'] ?? 5),
                'backup.file_index_cap' => (int) ($s['file_index_cap'] ?? 5000),
            ]);
            // DB-driven SMTP for notifications.
            if (! empty($s['smtp_host'])) {
                config([
                    'mail.default' => 'smtp',
                    'mail.mailers.smtp.host' => $s['smtp_host'],
                    'mail.mailers.smtp.port' => (int) ($s['smtp_port'] ?: 587),
                    'mail.mailers.smtp.username' => $s['smtp_username'] ?? null,
                    'mail.mailers.smtp.password' => $s['smtp_password'] ?? null,
                    'mail.from.address' => $s['mail_from'] ?: ('backups@' . parse_url(config('app.url'), PHP_URL_HOST)),
                    'mail.from.name' => $s['brand_name'] ?? config('brand.name'),
                ]);
            }
        } catch (\Throwable $e) {
            // DB not ready (e.g. during install); fall back to config defaults.
        }
    }
}
