<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforce the fleet security policies from General settings:
 *  - require_2fa: every user must set up a TOTP second factor.
 *  - force_password_days: passwords older than N days must be rotated.
 * The pages needed to satisfy each policy are allowlisted to avoid loops.
 */
class EnforceSecurityPolicy
{
    /** Route names a flagged user may still reach. */
    private array $allow = [
        'logout',
        'settings.2fa.show', 'settings.2fa.enable', 'settings.2fa.confirm', 'settings.2fa.disable',
        'settings.password.edit', 'settings.password.update',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $route = $request->route()?->getName();
        if ($route && in_array($route, $this->allow, true)) {
            return $next($request);
        }

        // 1) Mandatory two-factor.
        if (config('backup.require_2fa') && ! $user->hasTwoFactor()) {
            return redirect()->route('settings.2fa.show')
                ->with('warning', 'Two-factor authentication is required. Set it up to continue.');
        }

        // 2) Password rotation.
        $days = (int) config('backup.force_password_days', 0);
        if ($days > 0) {
            $changed = $user->password_changed_at;
            if (! $changed || $changed->lt(now()->subDays($days))) {
                return redirect()->route('settings.password.edit')
                    ->with('warning', "Your password is older than {$days} days. Please set a new one.");
            }
        }

        return $next($request);
    }
}
