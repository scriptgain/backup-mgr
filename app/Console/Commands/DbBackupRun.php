<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\RemoteDatabaseBackup;
use Illuminate\Console\Command;

/**
 * Runs an automated remote database backup. The nightly schedule invokes this;
 * it self-gates on the enabled flag + frequency. `--force` runs regardless
 * (used by the admin "Run Backup Now" request-flag path).
 */
class DbBackupRun extends Command
{
    protected $signature = 'db-backup:run {--force : run now regardless of schedule/enabled}';

    protected $description = 'Dump the database and ship it to the configured remote';

    public function handle(): int
    {
        if ($this->option('force')) {
            Setting::put('dbbackup_requested', '0');
        } else {
            if (! RemoteDatabaseBackup::enabled()) {
                $this->info('Automated backup is disabled.');

                return self::SUCCESS;
            }
            if ((Setting::get('dbbackup_frequency') ?: 'daily') === 'weekly' && ! now()->isMonday()) {
                $this->info('Weekly schedule: not running today.');

                return self::SUCCESS;
            }
        }

        $res = (new RemoteDatabaseBackup)->run();
        $this->line($res['message']);

        return $res['ok'] ? self::SUCCESS : self::FAILURE;
    }
}
