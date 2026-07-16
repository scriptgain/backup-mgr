<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncFolder extends Model
{
    protected $fillable = [
        'user_id', 'director_id', 'name', 'source_host_id', 'source_path',
        'targets', 'delete_extra', 'interval_minutes', 'enabled', 'status',
        'last_synced_at', 'last_result',
    ];

    protected $casts = [
        'targets' => 'array',
        'delete_extra' => 'boolean',
        'enabled' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function sourceHost(): BelongsTo
    {
        return $this->belongsTo(Host::class, 'source_host_id');
    }

    public function director(): BelongsTo
    {
        return $this->belongsTo(Director::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Resolve target rows [{host_id, path}] into hydrated hosts for display. */
    public function targetHosts()
    {
        $ids = collect($this->targets ?? [])->pluck('host_id');

        return Host::whereIn('id', $ids)->get()->keyBy('id');
    }

    public function isDue(): bool
    {
        if (! $this->enabled || $this->status === 'running') {
            return false;
        }

        return $this->last_synced_at === null
            || $this->last_synced_at->copy()->addMinutes($this->interval_minutes)->isPast();
    }

    /** Restrict to sync folders the given user may see (admin sees all). */
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where('user_id', $user->id);
    }
}
