<?php

namespace App\Http\Controllers;

use App\Support\Totp;
use Illuminate\Http\Request;

class TwoFactorController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $pendingSecret = $user->hasTwoFactor() ? null : session('2fa:setup_secret');

        return view('settings.two-factor', [
            'enabled' => $user->hasTwoFactor(),
            'secret' => $pendingSecret,
            'uri' => $pendingSecret ? Totp::uri($pendingSecret, $user->email, config('brand.name')) : null,
        ]);
    }

    /** Begin setup: generate a pending secret and show it for enrollment. */
    public function enable(Request $request)
    {
        if ($request->user()->hasTwoFactor()) {
            return back();
        }
        session(['2fa:setup_secret' => Totp::secret()]);

        return redirect()->route('settings.2fa.show');
    }

    /** Confirm setup by verifying a code, then activate 2FA. */
    public function confirm(Request $request)
    {
        $request->validate(['code' => ['required', 'string']]);
        $secret = session('2fa:setup_secret');
        if (! $secret) {
            return back()->with('status', 'Start setup again.');
        }
        if (! Totp::verify($secret, $request->input('code'))) {
            return back()->withErrors(['code' => 'That code is incorrect. Try again.']);
        }
        $request->user()->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
        ])->save();
        $request->session()->forget('2fa:setup_secret');

        return redirect()->route('settings.2fa.show')->with('status', 'Two-factor authentication is now on.');
    }

    public function disable(Request $request)
    {
        $request->validate(['password' => ['required', 'current_password']]);
        $request->user()->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return redirect()->route('settings.2fa.show')->with('status', 'Two-factor authentication turned off.');
    }
}
