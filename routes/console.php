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
