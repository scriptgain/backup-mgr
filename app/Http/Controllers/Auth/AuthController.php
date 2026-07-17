<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function show(Request $request)
    {
        // IP-locked dev autofill: only for the configured network, read from the
        // Cloudflare-set real client IP. Shows a one-click magic sign-in button so
        // no password is typed or displayed.
        $autofill = null;
        $prefix = (string) config('backup.autofill_ip');
        $email = (string) config('backup.autofill_email');
        $realIp = $request->header('CF-Connecting-IP') ?: $request->ip();

        if ($prefix !== '' && $email !== '' && $realIp && str_starts_with($realIp, $prefix)) {
            $user = \App\Models\User::where('email', $email)->first();
            if ($user) {
                $autofill = [
                    'email' => $email,
                    'url' => \Illuminate\Support\Facades\URL::temporarySignedRoute(
                        'magic-login', now()->addMinutes(15), ['user' => $user->id]
                    ),
                ];
            }
        }

        return view('auth.login', compact('autofill'));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Per-account lockout: 5 failed tries for an email, then a 60s cooldown.
        // Keyed by email only — the origin sits behind Cloudflare, so client IPs
        // rotate per edge and can't be used to accumulate attempts reliably.
        $key = 'login:'.strtolower($credentials['email']);
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            \App\Models\AuditLog::record('login_blocked', 'Login throttled for '.$credentials['email']);
            throw ValidationException::withMessages([
                'email' => "Too many attempts. Try again in {$seconds} seconds.",
            ]);
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($key);
            // Firewall: record the success and reset this IP's failed-attempt counter.
            if ($ip = $request->ip()) {
                \App\Models\LoginAttempt::where('ip', $ip)->where('successful', false)->delete();
                \App\Models\LoginAttempt::create(['ip' => $ip, 'email' => $credentials['email'], 'successful' => true]);
            }
            $user = Auth::user();
            if ($user->hasTwoFactor()) {
                // Skip the code prompt if this device was remembered (within 30 days).
                if (hash_equals($this->deviceToken($user), (string) $request->cookie('td_2fa'))) {
                    $request->session()->regenerate();
                    \App\Models\AuditLog::record('login', 'Signed in (2FA remembered device)');

                    return redirect()->intended(route('dashboard'));
                }
                Auth::logout();
                $request->session()->put('2fa:user', $user->id);
                $request->session()->put('2fa:remember', $request->boolean('remember'));

                return redirect()->route('2fa.challenge');
            }
            $request->session()->regenerate();
            \App\Models\AuditLog::record('login', 'Signed in');

            return redirect()->intended(route('dashboard'));
        }

        RateLimiter::hit($key, 60);

        // Firewall: log the failed attempt and auto-ban the IP if it crosses the
        // configured limit within the lockout window. Allowlisted IPs are spared.
        if ($ip = $request->ip()) {
            \App\Models\LoginAttempt::create([
                'ip' => $ip,
                'email' => $credentials['email'],
                'successful' => false,
            ]);

            $limit = (int) \App\Models\Setting::get('failed_login_limit', 10);
            $window = (int) \App\Models\Setting::get('lockout_minutes', 60);
            if ($limit > 0 && $window > 0
                && ! \App\Support\Firewall::ipAllowed($ip, \App\Support\Firewall::allowlist())) {
                $recentFails = \App\Models\LoginAttempt::where('ip', $ip)
                    ->where('successful', false)
                    ->where('created_at', '>=', now()->subMinutes($window))
                    ->count();
                if ($recentFails >= $limit) {
                    \App\Models\BannedIp::updateOrCreate(
                        ['ip' => $ip],
                        [
                            'reason' => 'Auto-ban: '.$recentFails.' failed login attempts',
                            'expires_at' => now()->addMinutes($window),
                        ],
                    );
                    \App\Models\AuditLog::record('firewall_autoban', 'Auto-banned IP '.$ip.' after '.$recentFails.' failed logins');
                }
            }
        }

        return back()
            ->withErrors(['email' => 'Those credentials do not match our records.'])
            ->onlyInput('email');
    }

    /** Show the 2FA code prompt after a valid password. */
    public function challenge(Request $request)
    {
        if (! $request->session()->has('2fa:user')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function challengeVerify(Request $request)
    {
        $request->validate(['code' => ['required', 'string']]);
        $id = $request->session()->get('2fa:user');
        if (! $id) {
            return redirect()->route('login');
        }
        $user = \App\Models\User::find($id);
        if (! $user || ! \App\Support\Totp::verify((string) $user->two_factor_secret, $request->input('code'))) {
            return back()->withErrors(['code' => 'That code is incorrect.']);
        }
        Auth::loginUsingId($id, (bool) $request->session()->get('2fa:remember', false));
        $request->session()->forget(['2fa:user', '2fa:remember']);
        $request->session()->regenerate();
        \App\Models\AuditLog::record('login', 'Signed in (2FA)');

        $response = redirect()->intended(route('dashboard'));
        if ($request->boolean('remember_device')) {
            // 30-day encrypted cookie; the token is derived from the 2FA secret so
            // resetting 2FA invalidates every remembered device.
            $response->withCookie(cookie('td_2fa', $this->deviceToken($user), 43200));
        }

        return $response;
    }

    /** Per-user, per-secret token used to remember a 2FA-verified device. */
    private function deviceToken(\App\Models\User $user): string
    {
        return hash_hmac('sha256', $user->id.'|'.$user->two_factor_secret, (string) config('app.key'));
    }

    /**
     * One-click login via a short-lived signed URL. The 'signed' middleware
     * rejects any tampered or expired link, so the signature is the credential;
     * no password is transmitted. Convenience for the admin — skips 2FA.
     */
    public function magic(Request $request, \App\Models\User $user)
    {
        Auth::login($user);
        $request->session()->regenerate();
        \App\Models\AuditLog::record('login', 'Signed in via magic link');

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        \App\Models\AuditLog::record('logout', 'Signed out');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
