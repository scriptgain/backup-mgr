<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorageDevice extends Model
{
    protected $fillable = ['director_id', 'name', 'mount_path', 'total_bytes', 'used_bytes', 'reported_at'];

    protected function casts(): array
    {
        return ['reported_at' => 'datetime'];
    }

    public function director(): BelongsTo
    {
        return $this->belongsTo(Director::class);
    }

    public function freeBytes(): ?int
    {
        if ($this->total_bytes === null) {
            return null;
        }

        return max(0, $this->total_bytes - (int) $this->used_bytes);
    }

    public function usedPercent(): ?int
    {
        if (! $this->total_bytes) {
            return null;
        }

        return (int) round(($this->used_bytes / $this->total_bytes) * 100);
    }
}
