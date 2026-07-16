<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    protected $fillable = ['user_id', 'name', 'token', 'last_used_at', 'expires_at'];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Issue a new token. Returns [ApiToken $model, string $plaintext].
     * Only the sha256 hash is stored; the plaintext is shown once.
     */
    public static function issue(User $user, string $name, ?\DateTimeInterface $expiresAt = null): array
    {
        $plain = 'vlt_' . Str::random(48);
        $token = static::create([
            'user_id' => $user->id,
            'name' => $name,
            'token' => hash('sha256', $plain),
            'expires_at' => $expiresAt,
        ]);

        return [$token, $plain];
    }

    /** Find a live token by its plaintext value, or null. */
    public static function findByPlaintext(string $plain): ?self
    {
        $token = static::where('token', hash('sha256', $plain))->first();

        if (! $token) {
            return null;
        }
        if ($token->expires_at && $token->expires_at->isPast()) {
            return null;
        }

        return $token;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
