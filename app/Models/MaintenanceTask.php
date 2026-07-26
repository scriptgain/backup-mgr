<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A manually requested prune / kopia maintenance pass over one repository.
 *
 * Backups reach the agent as a Run and restores as a Restore; this is the third
 * kind of work the master can hand out, and the only one not tied to a backup.
 * The executing agent is pinned at creation (see the migration) because a
 * filesystem repository is only reachable from the host that holds the path.
 */
class MaintenanceTask extends Model
{
    public const KINDS = ['prune', 'maintenance', 'both'];

    protected $fillable = [
        'repository_id', 'host_id', 'user_id', 'kind', 'status',
        'started_at', 'finished_at', 'log', 'error',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(Host::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['queued', 'running'], true);
    }

    /** Whether this task applies retention and expires old snapshots. */
    public function prunes(): bool
    {
        return in_array($this->kind, ['prune', 'both'], true);
    }

    /** Whether this task runs kopia compaction + GC. */
    public function maintains(): bool
    {
        return in_array($this->kind, ['maintenance', 'both'], true);
    }

    public function kindLabel(): string
    {
        return match ($this->kind) {
            'prune' => 'Prune',
            'maintenance' => 'Maintenance',
            default => 'Prune + Maintenance',
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'success' => 'success',
            'failed' => 'danger',
            'running' => 'info',
            default => 'neutral',
        };
    }

    /** Wall-clock duration, or null while the task has not finished. */
    public function duration(): ?string
    {
        if (! $this->started_at || ! $this->finished_at) {
            return null;
        }
        $s = $this->started_at->diffInSeconds($this->finished_at);

        return $s < 60 ? "{$s}s" : floor($s / 60) . 'm ' . ($s % 60) . 's';
    }
}
