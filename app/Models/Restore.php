<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Restore extends Model
{
    protected $fillable = [
        'run_id', 'host_id', 'snapshot_id', 'paths', 'target_path', 'status', 'log',
        'overwrite', 'restore_ownership', 'restore_permissions', 'strip_paths', 'dry_run',
    ];

    protected function casts(): array
    {
        return [
            'paths' => 'array',
            'restore_ownership' => 'boolean',
            'restore_permissions' => 'boolean',
            'strip_paths' => 'boolean',
            'dry_run' => 'boolean',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(Run::class);
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(Host::class);
    }
}
