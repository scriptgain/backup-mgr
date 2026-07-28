<?php

namespace App\Rules;

use Closure;
use Cron\CronExpression;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A cron expression this master can actually schedule against.
 *
 * Schedule fields used to be accepted as free strings, so a typo was stored
 * happily and simply never fired. Anything the scheduler will silently skip
 * should fail at the form instead.
 */
class ValidCron implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cron = trim((string) $value);
        if ($cron === '') {
            return;     // blank means "no schedule"; use `nullable` to allow it
        }
        if (! CronExpression::isValidExpression($cron)) {
            $fail('The :attribute must be a valid cron expression, for example "30 4 * * *".');
        }
    }
}
