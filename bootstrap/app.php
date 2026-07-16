<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust the local reverse proxy so $request->ip() is the real client IP
        // (needed for firewall IP bans / auto-ban behind nginx).
        $middleware->trustProxies(at: ['127.0.0.1', '::1']);
        $middleware->alias([
            'api.token' => \App\Http\Middleware\AuthenticateApiToken::class,
            'agent.auth' => \App\Http\Middleware\AuthenticateAgent::class,
            'security.policy' => \App\Http\Middleware\EnforceSecurityPolicy::class,
            'firewall' => \App\Http\Middleware\FirewallGuard::class,
            'setup' => \App\Http\Middleware\EnsureSetup::class,
        ]);
        // Perimeter guard on every web request: IP bans + optional allowlist.
        $middleware->web(append: [
            \App\Http\Middleware\FirewallGuard::class,
        ]);

        // First-run guard: force a fresh install through /setup until complete.
        $middleware->web(append: [
            \App\Http\Middleware\EnsureSetup::class,
        ]);
        // Run the setup gate BEFORE auth so a brand-new install (no admin yet,
        // no session) lands on /setup instead of dead-ending at /login. Sits
        // just after StartSession/ShareErrorsFromSession so flash errors work.
        $middleware->prependToPriorityList(
            before: \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            prepend: \App\Http\Middleware\EnsureSetup::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
