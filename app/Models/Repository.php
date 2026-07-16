<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Repository extends Model
{
    use \App\Models\Concerns\Auditable;
    protected $fillable = [
        'director_id', 'name', 'backend', 'config',
        'access_key_id', 'secret_access_key', 'password', 'compression', 'status',
    ];

    protected $hidden = ['secret_access_key', 'password'];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'secret_access_key' => 'encrypted',
            'password' => 'encrypted',
        ];
    }

    public function director(): BelongsTo
    {
        return $this->belongsTo(Director::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(BackupJob::class);
    }
}
