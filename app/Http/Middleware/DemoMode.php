<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Read-only public demo. When backup.demo is on:
 *  - visitors are auto-signed-in as the demo user (no password, no /setup),
 *  - every state-changing request (POST/PUT/PATCH/DELETE) is blocked with a
 *    friendly notice, so nobody can add, edit, delete, or change settings
 *    and passwords.
 * All seeded data is fake. Enable via DEMO_MODE=true on the demo host only.
 */
class DemoMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('backup.demo')) {
            return $next($request);
        }

        // Auto-login the demo user so the whole panel is explorable password-free.
        if (! Auth::check()) {
            $id = User::where('email', 'demo@scriptgain.com')->value('id') ?? User::min('id');
            if ($id) {
                Auth::loginUsingId($id);
            }
        }

        // File managers are ALWAYS disabled in the demo: never expose the host
        // or server filesystem, on any verb or via a direct URL.
        if ($request->routeIs('hosts.browse', 'hosts.mkdir', 'snapshots.browse') || $request->is('*/browse', '*/mkdir')) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['path' => '', 'parent' => null, 'entries' => [], 'error' => 'File browsing is disabled in the demo.'], 200);
            }

            return back()->with('warning', 'File browsing is disabled in the demo.');
        }

        // Block everything that would change state.
        if (! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            $msg = 'This is a read-only demo. Adding, editing, deleting, and changing settings or passwords are disabled.';

            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'demo' => true, 'message' => $msg], 422);
            }

            return back()->with('warning', $msg);
        }

        return $next($request);
    }
}
