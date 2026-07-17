<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Fire due backup jobs every minute (installer wires `schedule:run` into cron).
Schedule::command('backup:dispatch-due')->everyMinute()->withoutOverlapping();

// Nightly catalog housekeeping: prune old run history + audit rows.
Schedule::command('backup:housekeeping')->dailyAt('03:30')->withoutOverlapping();

// Self-update: check nightly and auto-apply when a newer signed release exists,
// unless the operator has turned auto-update off.
Schedule::command('app:update')
    ->dailyAt('04:10')
    ->when(fn () => \App\Services\UpdateService::autoEnabled())
    ->withoutOverlapping();

// Admin "Update Now" requests: applied within a minute by the scheduler so the
// command runs with the right PHP binary/user rather than shelling out from fpm.
Schedule::command('app:update')
    ->everyMinute()
    ->when(fn () => \App\Models\Setting::get('update_requested') === '1')
    ->withoutOverlapping();

// Automated remote database backup at the configured time; the command
// self-gates on the enabled flag + daily/weekly frequency.
Schedule::command('db-backup:run')
    ->dailyAt(rescue(fn () => \App\Models\Setting::get('dbbackup_time'), null, false) ?: '02:30')
    ->withoutOverlapping();

// Admin "Run Backup Now" requests, serviced within a minute.
Schedule::command('db-backup:run --force')
    ->everyMinute()
    ->when(fn () => \App\Models\Setting::get('dbbackup_requested') === '1')
    ->withoutOverlapping();
