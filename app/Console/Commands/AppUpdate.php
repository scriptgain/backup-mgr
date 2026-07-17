<?php

namespace App\Console\Commands;

use App\Services\LicenseClient;
use App\Services\UpdateService;
use Illuminate\Console\Command;

/**
 * Checks for and applies the latest release.
 *
 *   php artisan app:update           refresh license + apply if a newer build exists
 *   php artisan app:update --check   only report current vs latest, never apply
 *
 * The admin "Update Now" button and the daily scheduler both drive this command
 * (the scheduler only when auto-update is enabled).
 */
class AppUpdate extends Command
{
    protected $signature = 'app:update {--check : only report status, do not apply}';

    protected $description = 'Check for and apply the latest BackupMGR release';

    public function handle(): int
    {
        // Clear any pending admin "Update Now" request now that we're running.
        \App\Models\Setting::put('update_requested', '0');

        // A fresh license check refreshes the signed version info as a side effect.
        LicenseClient::refresh();

        $st = UpdateService::status();
        $this->line('Current: ' . $st['current'] . '   Latest: ' . ($st['latest'] ?: 'unknown'));

        if ($this->option('check')) {
            $this->info($st['available'] ? 'Update available.' : 'Up to date.');

            return self::SUCCESS;
        }

        if (! $st['available']) {
            $this->info('Already up to date.');

            return self::SUCCESS;
        }

        $res = (new UpdateService)->apply(fn ($m) => $this->line($m));
        $res['ok'] ? $this->info($res['message']) : $this->error($res['message']);

        return $res['ok'] ? self::SUCCESS : self::FAILURE;
    }
}
