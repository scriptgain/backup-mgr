<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\LicenseClient;
use App\Services\UpdateService;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    public function show()
    {
        return view('settings.updates', ['status' => UpdateService::status()]);
    }

    /** Synchronous, safe: re-check the license, which refreshes the signed version info. */
    public function check()
    {
        LicenseClient::refresh();

        return back()->with('status', 'Checked for updates.');
    }

    /**
     * Queue an update. Rather than shell out to the CLI from php-fpm, we set a
     * flag the scheduler (already running every minute) acts on, so it runs with
     * the correct PHP binary and user. Applies within ~a minute.
     */
    public function apply()
    {
        if (! UpdateService::available()) {
            return back()->with('status', 'Already up to date.');
        }
        Setting::put('update_requested', '1');
        Setting::put('update_last_result', 'queued: requested ' . now()->toIso8601String());

        return back()->with('status', 'Update queued — it will start within a minute. Refresh for the result.');
    }

    public function toggleAuto(Request $request)
    {
        Setting::put('update_auto', $request->boolean('auto') ? '1' : '0');

        return back()->with('status', 'Automatic updates ' . ($request->boolean('auto') ? 'enabled.' : 'disabled.'));
    }
}
