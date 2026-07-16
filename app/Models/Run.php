<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Run extends Model
{
    protected $fillable = [
        'backup_job_id', 'status', 'started_at', 'finished_at',
        'bytes_in', 'bytes_uploaded', 'files', 'snapshot_id', 'log', 'error', 'file_index',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'file_index' => 'array',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(BackupJob::class, 'backup_job_id');
    }
}
