<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = ['user_id', 'action', 'subject_type', 'subject_id', 'description', 'ip'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Record an audit entry. Best effort — never throws into the caller. */
    public static function record(string $action, string $description, ?Model $subject = null): void
    {
        try {
            static::create([
                'user_id' => auth()->id(),
                'action' => $action,
                'subject_type' => $subject ? class_basename($subject) : null,
                'subject_id' => $subject?->getKey(),
                'description' => $description,
                'ip' => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            // Auditing must never break the request.
        }
    }
}
