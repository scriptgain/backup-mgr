<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BannedIp extends Model
{
    protected $fillable = ['ip', 'reason', 'expires_at', 'created_by'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /** Bans that are still in force (permanent, or not yet expired). */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

    /** Is this IP currently under an active ban? */
    public static function isBanned(string $ip): bool
    {
        return static::query()->active()->where('ip', $ip)->exists();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
