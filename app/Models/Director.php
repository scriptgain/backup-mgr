<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Director extends Model
{
    use \App\Models\Concerns\Auditable;
    protected $fillable = ['location_id', 'user_id', 'name', 'slug', 'region', 'hostname', 'port', 'is_local', 'status', 'version', 'notes'];

    protected $hidden = ['api_key'];

    protected function casts(): array
    {
        return [
            'is_local' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Limit to directors a user may see: admins see all, others see their own. */
    public function scopeVisibleTo($query, ?User $user)
    {
        if ($user && ! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    public function hosts(): HasMany
    {
        return $this->hasMany(Host::class);
    }

    public function storageDevices(): HasMany
    {
        return $this->hasMany(StorageDevice::class);
    }

    public function repositories(): HasMany
    {
        return $this->hasMany(Repository::class);
    }
}
