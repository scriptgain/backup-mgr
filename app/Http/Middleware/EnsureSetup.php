<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * First-run guard: force a fresh install through the /setup wizard until an
 * admin exists and a license has been dealt with. Once setup_complete === '1',
 * the wizard is closed off and /setup redirects home.
 *
 * Applied to the web group only; the API and agent groups are untouched so
 * enrolled agents keep polling regardless of wizard state.
 */
class EnsureSetup
{
    /** Path prefixes that must stay reachable while setup is pending. */
    private array $allowPrefixes = [
        'setup',
        'login',
        'logout',
        'brand',   // favicon routes used by the standalone pages
        'up',      // health check
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $needsSetup = Setting::get('setup_complete') !== '1';

        if ($needsSetup) {
            // Let the wizard, auth, and public routes through; redirect the rest.
            if (! $this->isAllowedWhilePending($request)) {
                return redirect()->route('setup.index');
            }

            return $next($request);
        }

        // Setup is done — never show the wizard again.
        if ($request->is('setup', 'setup/*')) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }

    private function isAllowedWhilePending(Request $request): bool
    {
        foreach ($this->allowPrefixes as $prefix) {
            if ($request->is($prefix, $prefix.'/*')) {
                return true;
            }
        }

        return false;
    }
}
