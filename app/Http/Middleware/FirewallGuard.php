<?php

namespace App\Http\Middleware;

use App\Models\BannedIp;
use App\Models\Setting;
use App\Support\Firewall;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Perimeter guard on the web group. The client IP comes from $request->ip(),
 * which is trustworthy because trustProxies is pinned to the loopback proxy.
 *
 *  1. An actively banned IP is refused with a plain 403.
 *  2. When the access-limit allowlist is enabled, only listed IPs/CIDRs pass;
 *     everything else is refused with a plain 403.
 *
 * Recovery from a lockout: `php artisan firewall:clear` over SSH.
 */
class FirewallGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        if ($ip && BannedIp::isBanned($ip)) {
            return response('Access denied.', 403);
        }

        if (Setting::get('access_limit_enabled') === '1'
            && ! Firewall::ipAllowed($ip, Firewall::allowlist())) {
            return response('Access denied.', 403);
        }

        return $next($request);
    }
}
