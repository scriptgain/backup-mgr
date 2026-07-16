<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    /** Table carries only created_at. */
    const UPDATED_AT = null;

    protected $fillable = ['ip', 'email', 'successful', 'created_at'];

    protected $casts = [
        'successful' => 'bool',
        'created_at' => 'datetime',
    ];
}
