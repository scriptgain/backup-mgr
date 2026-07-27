<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Host;
use App\Models\MaintenanceTask;
use App\Models\Repository;
use App\Models\Run;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Delete restore points on demand.
 *
 * A snapshot lives in the repository, not in this database, so deleting one is
 * work for the agent that can reach the repository. That is the same channel a
 * manual prune uses (MaintenanceTask), with the snapshots named outright rather
 * than planned from retention: see AgentController::maintenancePoll.
 *
 * Work is grouped one task per repository, because the executing agent is pinned
 * per repository. The run rows are left alone here: they are retired when the
 * agent reports which snapshots survived, so a task that never runs cannot make a
 * restore point vanish from the UI while it still exists on disk.
 */
class SnapshotDeleter
{
    /**
     * Queue deletion of every given run's snapshot.
     *
     * @param  Collection<int, Run>  $runs  runs already checked for visibility
     * @return array{snapshots:int, tasks:int, notes:array<int, string>}
     */
    public function queue(Collection $runs, User $user): array
    {
        $snapshots = 0;
        $tasks = 0;
        $notes = [];

        $runs = $runs->filter(fn (Run $run) => $run->snapshot_id && ! $run->snapshot_expired_at);

        foreach ($runs->groupBy(fn (Run $run) => $run->job?->repository_id ?? 0) as $repositoryId => $group) {
            $ids = $group->pluck('snapshot_id')->filter()->unique()->values()->all();
            $count = count($ids);
            $label = $count.' '.str('snapshot')->plural($count);

            $repository = $repositoryId
                ? Repository::with('director.gatewayAgent')->find($repositoryId)
                : null;

            if (! $repository) {
                $notes[] = "{$label} skipped: no repository is recorded for their job.";
                continue;
            }

            $gateway = $repository->director?->gatewayAgent;
            if (! $gateway) {
                $notes[] = "{$label} skipped: no agent host in Director \"{$repository->director?->name}\" can reach \"{$repository->name}\".";
                continue;
            }

            // Deletes are not blocked by a task already in flight the way a manual
            // prune is: the agent claims one task per check-in, so they simply run
            // in turn. A delete still waiting to be claimed absorbs the new ids,
            // so repeated deletes cannot pile up a row per click.
            $task = MaintenanceTask::where('repository_id', $repository->id)
                ->where('kind', 'delete')
                ->where('status', 'queued')
                ->latest('id')
                ->first();

            if ($task) {
                $ids = array_values(array_unique(array_merge($task->snapshotIds(), $ids)));
                $task->forceFill(['snapshot_ids' => $ids, 'host_id' => $gateway->id])->save();
                AuditLog::record('queued', "Delete of {$label} added to the queued delete for repository \"{$repository->name}\"", $task);
            } else {
                $task = MaintenanceTask::create([
                    'repository_id' => $repository->id,
                    'host_id' => $gateway->id,
                    'user_id' => $user->id,
                    'kind' => 'delete',
                    'snapshot_ids' => $ids,
                    'status' => 'queued',
                ]);
                AuditLog::record('queued', "Delete of {$label} queued for repository \"{$repository->name}\"", $task);
            }

            $snapshots += $count;
            $tasks++;

            if (! $gateway->supportsMaintenanceTasks()) {
                $notes[] = "{$gateway->name} reports agent ".($gateway->agent_version ?: 'unknown')
                    .', which cannot run this task. Update it to '.Host::MIN_AGENT_VERSION_FOR_TASKS.' or newer.';
            }
        }

        return ['snapshots' => $snapshots, 'tasks' => $tasks, 'notes' => $notes];
    }

    /** Turn a queue() result into the flash message pair the views expect. */
    public function flash($redirect, array $result)
    {
        $notes = implode(' ', $result['notes']);

        if ($result['snapshots'] === 0) {
            return $redirect->with('error', $notes ?: 'Nothing was queued for deletion.');
        }

        $label = $result['snapshots'].' '.str('snapshot')->plural($result['snapshots']);
        $where = $result['tasks'] > 1 ? " across {$result['tasks']} repositories" : '';
        $message = "Deletion of {$label} queued{$where}. It starts on the next agent check-in; the restore point disappears once the agent confirms it is gone.";

        return $notes
            ? $redirect->with('status', $message)->with('warning', $notes)
            : $redirect->with('status', $message);
    }
}
