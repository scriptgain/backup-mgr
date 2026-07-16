<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function edit()
    {
        $license = [
            'key' => Setting::get('license_key'),
            'plan' => Setting::get('license_plan'),
            'status' => Setting::get('license_status', 'unlicensed'),
            'checked_at' => Setting::get('license_checked_at'),
            'product' => config('brand.name', 'BackupMGR'),
        ];

        return view('settings.license', compact('license'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'license_key' => ['nullable', 'string', 'max:200'],
        ]);
        $key = trim((string) ($data['license_key'] ?? ''));

        Setting::put('license_key', $key ?: null);
        Setting::put('license_status', $key ? 'unverified' : 'unlicensed');
        AuditLog::record('license', $key ? 'Updated license key' : 'Cleared license key');

        return back()->with('status', $key ? 'License Key Saved. Run Sync to validate it.' : 'License Key Cleared.');
    }

    /**
     * Re-sync / validate the stored key. Online validation against
     * scriptgain.com (/v1/validate, RSA-signed) is wired later; for now this
     * stores the key and stamps the check time.
     */
    public function sync(Request $request)
    {
        $key = Setting::get('license_key');
        if (! $key) {
            return back()->with('status', 'Enter a License Key first.');
        }

        Setting::put('license_checked_at', now()->toDateTimeString());
        AuditLog::record('license', 'Synced license (online validation pending)');

        return back()->with('status', 'Sync recorded. Online validation against ScriptGain will be enabled soon; your key is stored and applied.');
    }
}
