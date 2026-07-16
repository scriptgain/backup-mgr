<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Records create/delete audit entries for a model.
 * The human label comes from the model's `name`, else its key.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $m) {
            AuditLog::record('created', static::auditLabel($m).' created', $m);
        });

        static::deleted(function (Model $m) {
            AuditLog::record('deleted', static::auditLabel($m).' deleted', $m);
        });
    }

    protected static function auditLabel(Model $m): string
    {
        $name = $m->name ?? $m->email ?? $m->getKey();

        return class_basename($m).' "'.$name.'"';
    }
}
