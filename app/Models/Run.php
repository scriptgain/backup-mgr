<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Run extends Model
{
    protected $fillable = [
        'backup_job_id', 'status', 'started_at', 'finished_at',
        'bytes_in', 'bytes_uploaded', 'files', 'snapshot_id', 'snapshot_expired_at',
        'log', 'error', 'file_index',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'snapshot_expired_at' => 'datetime',
            'file_index' => 'array',
        ];
    }

    /**
     * Runs that still hold a restorable snapshot.
     *
     * A pruned snapshot is gone from the repository, but its run row stays as
     * history: listing it as a restore point would offer a restore that cannot
     * succeed.
     */
    public function scopeRestorable($query)
    {
        return $query->whereNotNull('snapshot_id')->whereNull('snapshot_expired_at');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(BackupJob::class, 'backup_job_id');
    }
}
