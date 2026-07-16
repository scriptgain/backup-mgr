<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use App\Services\LicenseClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * One-time first-run setup wizard for BackupMGR.
 *
 * Two steps:
 *   1. Create the first admin account (guest).
 *   2. Enter/activate a license key (authed as that admin).
 *
 * Access is governed entirely by the EnsureSetup middleware; these routes are
 * deliberately NOT behind the auth middleware so step 1 can run as a guest.
 * Because this is a lenient backup product, the license step never hard-locks:
 * an unreachable/unverified vendor still lets the operator finish.
 */
class SetupController extends Controller
{
    public function index()
    {
        // Step 1: no admin yet -> create one.
        if (User::where('role', 'admin')->doesntExist()) {
            return view('setup.admin');
        }

        // Step 2: admin exists but no confirmed-good license -> license step.
        $state = LicenseClient::status()['state'] ?? 'unlicensed';
        if (! LicenseClient::key() || $state !== 'valid') {
            return view('setup.license', [
                'state' => $state,
            ]);
        }

        // Everything satisfied — mark complete and go home.
        Setting::put('setup_complete', '1');

        return redirect()->route('dashboard');
    }

    public function storeAdmin(Request $request)
    {
        // Guard: don't let this be used to add a second admin once one exists.
        if (User::where('role', 'admin')->exists()) {
            return redirect()->route('setup.index');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            // password cast as 'hashed' on the model — plain value is hashed on save.
            'password' => $data['password'],
            'role' => 'admin',
        ]);

        Auth::login($user);

        return redirect()->route('setup.index');
    }

    public function storeLicense(Request $request)
    {
        $data = $request->validate([
            'key' => ['nullable', 'string', 'max:255'],
        ]);

        // Secondary "I'll add my license later" action — finish without a key.
        if ($request->input('action') === 'skip' || blank($data['key'] ?? null)) {
            Setting::put('setup_complete', '1');

            return redirect()->route('dashboard')
                ->with('warning', 'Setup complete. Add a license key later from Settings → License.');
        }

        Setting::put('license_key', trim($data['key']));
        $r = LicenseClient::refresh();
        $state = $r['state'] ?? 'unverified';
        $message = $r['message'] ?? '';

        // Active and signature-verified: done.
        if ($state === 'valid') {
            Setting::put('setup_complete', '1');

            return redirect()->route('dashboard')
                ->with('success', 'License activated — setup complete.');
        }

        // Definitive rejection: clear the bad key and let them retry.
        if (in_array($state, ['invalid', 'unlicensed', 'not_found'], true)) {
            Setting::put('license_key', null);

            return redirect()->route('setup.index')
                ->withErrors(['key' => 'That license key was rejected: '.$message]);
        }

        // Network couldn't confirm (unverified / grace / unreachable) — the key is
        // stored and will verify later. Never block finishing a backup product.
        Setting::put('setup_complete', '1');

        return redirect()->route('dashboard')
            ->with('warning', 'Setup complete. Your license will verify automatically once the vendor is reachable.');
    }
}
