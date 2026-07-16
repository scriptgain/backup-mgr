<?php

namespace App\Http\Middleware;

use App\Http\Controllers\PublicShareController;
use App\Models\Share;
use Closure;
use Illuminate\Http\Request;

class ServeCustomDomainShare
{
    /**
     * If the request arrives on a user's own domain (mapped to a Share via
     * custom_domain), serve that share at the domain root. Otherwise fall
     * through to normal routing on the Manager's own host.
     */
    public function handle(Request $request, Closure $next)
    {
        // Only GET/HEAD are served as files; everything else routes normally.
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        $host = strtolower($request->getHost());
        $appHost = strtolower((string) parse_url(config('app.url'), PHP_URL_HOST));

        if ($host === '' || $host === $appHost) {
            return $next($request);
        }

        $share = Share::where('custom_domain', $host)->first();
        if (! $share || $share->isExpired()) {
            return $next($request);
        }

        return app(PublicShareController::class)
            ->viaDomain($request, $share, trim($request->path(), '/'));
    }
}
