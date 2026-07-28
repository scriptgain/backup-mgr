<?php

namespace App\Console\Commands;

use App\Http\Controllers\MaintenanceController;
use App\Models\AuditLog;
use App\Models\Host;
use App\Models\MaintenanceTask;
use App\Models\Repository;
use App\Models\Setting;
use Cron\CronExpression;
use Illuminate\Console\Command;

/**
 * Queue repository prune passes on a schedule.
 *
 * The prune an agent runs after a backup can only expire snapshots that share a
 * kopia source, so anything recorded under a one-off source (every agentless
 * pull before the agent learned --override-source) is invisible to it and stays
 * forever. The master's own plan, built from run history, is the only thing that
 * can retire those, and it reaches an agent solely through a MaintenanceTask.
 * Without this command that task only ever existed when an operator pressed the
 * button, which is why a repository could report "pruned per retention" nightly
 * and still never lose a snapshot.
 */
class PruneDue extends Command
{
    protected $signature = 'backup:prune-due';

    protected $description = 'Queue a prune pass for repositories whose prune schedule is due now.';

    public function handle(): int
    {
        $s = Setting::map();

        $globalCron = trim($s['auto_prune_cron'] ?? '');
        $globalDue = ($s['auto_prune_enabled'] ?? '0') === '1' && $this->isDue($globalCron);

        // Maintenance is what actually returns freed space to the filesystem, so
        // fold it in whenever its own settings allow it right now.
        $kind = MaintenanceController::allowedNow($s) ? 'both' : 'prune';

        $repositories = Repository::with([
            'director.gatewayAgent',
            'activeMaintenanceTask',
            'jobs' => fn ($q) => $q->where('enabled', true),
        ])->get();

        $queued = 0;
        foreach ($repositories as $repo) {
            $reason = $this->dueReason($repo, $globalDue);
            if (! $reason) {
                continue;
            }
            if ($this->queue($repo, $kind, $reason)) {
                $queued++;
            }
        }

        $this->info("Queued {$queued} prune task(s).");

        return self::SUCCESS;
    }

    /**
     * Why this repository is due right now, or null if it is not.
     *
     * A job's own prune schedule adds a pass for its repository and fires even
     * when the global schedule is off, so a single noisy job can be pruned more
     * often than the fleet without turning the fleet's schedule into its own.
     */
    private function dueReason(Repository $repo, bool $globalDue): ?string
    {
        foreach ($repo->jobs as $job) {
            if ($this->isDue((string) $job->prune_schedule_cron)) {
                return "prune schedule on job \"{$job->name}\"";
            }
        }

        return $globalDue ? 'the global prune schedule' : null;
    }

    private function isDue(string $cron): bool
    {
        $cron = trim($cron);

        return $cron !== ''
            && CronExpression::isValidExpression($cron)
            && (new CronExpression($cron))->isDue();
    }

    /** Create the task, or explain why this repository has to be skipped. */
    private function queue(Repository $repo, string $kind, string $reason): bool
    {
        // kopia takes a repository-wide maintenance lock, so a second concurrent
        // pass would only fail on it.
        if ($repo->activeMaintenanceTask) {
            $this->line("Repository {$repo->id} already has a {$repo->activeMaintenanceTask->status} task; skipping.");

            return false;
        }

        $gateway = $repo->director?->gatewayAgent;
        if (! $gateway) {
            $this->warn("Repository \"{$repo->name}\" is due but no agent host in its Director can reach it; skipping.");

            return false;
        }
        // Queueing work no agent understands would leave a task sitting forever.
        if (! $gateway->supportsMaintenanceTasks()) {
            $this->warn("Repository \"{$repo->name}\" is due but {$gateway->name} reports agent "
                .($gateway->agent_version ?: 'unknown').', which predates '
                .Host::MIN_AGENT_VERSION_FOR_TASKS.'; skipping.');

            return false;
        }

        $task = MaintenanceTask::create([
            'repository_id' => $repo->id,
            'host_id' => $gateway->id,
            'user_id' => null,          // scheduled, not operator-driven
            'kind' => $kind,
            'status' => 'queued',
        ]);

        AuditLog::record('queued', "{$task->kindLabel()} queued for repository \"{$repo->name}\" by {$reason}", $task);
        $this->line("Queued {$task->kindLabel()} for \"{$repo->name}\" ({$reason}).");

        return true;
    }
}
