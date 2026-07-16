<?php

namespace App\Console\Commands;

use App\Models\BackupJob;
use App\Models\Run;
use Cron\CronExpression;
use Illuminate\Console\Command;

class DispatchDueJobs extends Command
{
    protected $signature = 'backup:dispatch-due';

    protected $description = 'Queue runs for backup jobs whose schedule is due now.';

    public function handle(): int
    {
        $jobs = BackupJob::where('enabled', true)->whereNotNull('schedule_cron')
            ->with('host:id,director_id')->get();
        $queued = 0;

        // Concurrency cap per Director (queued + running runs).
        $maxConcurrent = max(1, (int) config('backup.max_concurrent_jobs', 2));
        $activePerDirector = $this->activeRunsPerDirector();

        foreach ($jobs as $job) {
            if (! CronExpression::isValidExpression($job->schedule_cron)) {
                continue;
            }
            if (! (new CronExpression($job->schedule_cron))->isDue()) {
                continue;
            }
            // Don't pile up: skip if a run is already queued or running.
            $busy = Run::where('backup_job_id', $job->id)
                ->whereIn('status', ['queued', 'running'])
                ->exists();
            if ($busy) {
                continue;
            }
            // Respect the per-Director concurrency limit.
            $dir = $job->host?->director_id;
            if ($dir !== null && ($activePerDirector[$dir] ?? 0) >= $maxConcurrent) {
                $this->line("Director {$dir} at concurrency cap ({$maxConcurrent}); deferring job {$job->id}.");
                continue;
            }
            Run::create(['backup_job_id' => $job->id, 'status' => 'queued']);
            if ($dir !== null) {
                $activePerDirector[$dir] = ($activePerDirector[$dir] ?? 0) + 1;
            }
            $queued++;
        }

        $this->info("Dispatched {$queued} run(s).");

        return self::SUCCESS;
    }

    /** Map of director_id => count of queued/running runs right now. */
    private function activeRunsPerDirector(): array
    {
        $counts = [];
        Run::whereIn('status', ['queued', 'running'])
            ->with('job.host:id,director_id')
            ->get()
            ->each(function ($run) use (&$counts) {
                $dir = $run->job?->host?->director_id;
                if ($dir !== null) {
                    $counts[$dir] = ($counts[$dir] ?? 0) + 1;
                }
            });

        return $counts;
    }
}
