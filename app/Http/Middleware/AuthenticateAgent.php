<?php

namespace App\Http\Middleware;

use App\Models\Host;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAgent
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();
        if (! $bearer) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $host = Host::where('api_key', hash('sha256', $bearer))->first();
        if (! $host) {
            return response()->json(['message' => 'Invalid agent key.'], 401);
        }

        $request->attributes->set('agent_host', $host);

        return $next($request);
    }
}
