<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RetentionPolicy extends Model
{
    protected $fillable = [
        'name', 'keep_latest', 'keep_hourly', 'keep_daily',
        'keep_weekly', 'keep_monthly', 'keep_annual',
    ];

    public function jobs(): HasMany
    {
        return $this->hasMany(BackupJob::class);
    }
}
