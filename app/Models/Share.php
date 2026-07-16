<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Share extends Model
{
    protected $fillable = [
        'user_id', 'host_id', 'name', 'slug', 'custom_domain', 'token', 'path',
        'visibility', 'password', 'allow_uploads', 'allow_listing',
        'expires_at', 'downloads',
    ];

    protected $casts = [
        'allow_uploads' => 'boolean',
        'allow_listing' => 'boolean',
        'expires_at' => 'datetime',
        'downloads' => 'integer',
    ];

    protected $hidden = ['password'];

    protected static function booted(): void
    {
        static::creating(function (Share $s) {
            $s->slug = $s->slug ?: Str::slug($s->name).'-'.Str::lower(Str::random(4));
            $s->token = $s->token ?: Str::random(40);
            $s->path = $s->path ?: static::baseDir().'/'.$s->slug;
        });
    }

    /** Root directory that holds every share's folder. */
    public static function baseDir(): string
    {
        return rtrim(config('backup.shares_base', storage_path('app/shares')), '/');
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(Host::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return ! $this->isExpired();
    }

    /** Absolute path to this share's folder on disk. */
    public function absPath(): string
    {
        return $this->path ?: static::baseDir().'/'.$this->slug;
    }

    /** CDN-style public URL base (only meaningful when public). */
    public function publicUrl(): string
    {
        return url('/s/'.$this->slug);
    }

    /** Unguessable link that works regardless of visibility. */
    public function linkUrl(): string
    {
        return url('/d/'.$this->token);
    }

    /** The user's own domain serving this share at its root, if configured. */
    public function domainUrl(): ?string
    {
        return $this->custom_domain ? 'https://'.$this->custom_domain : null;
    }

    /** The best URL to advertise for this share. */
    public function primaryUrl(): string
    {
        return $this->domainUrl() ?? ($this->isPublic() ? $this->publicUrl() : $this->linkUrl());
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where('user_id', $user->id);
    }
}
