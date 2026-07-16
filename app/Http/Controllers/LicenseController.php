<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Services\LicenseClient;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function edit()
    {
        $status = LicenseClient::status();
        $payload = $status['license'] ?? [];

        $license = [
            'key' => LicenseClient::key(),
            'plan' => $payload['type'] ?? ($payload['plan'] ?? null),
            'status' => $status['state'],
            'expires_at' => $payload['expires_at'] ?? null,
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

        if (! $key) {
            AuditLog::record('license', 'Cleared license key');

            return back()->with('status', 'License Key Cleared.');
        }

        // Validate the newly-saved key immediately against ScriptGain.
        $result = LicenseClient::refresh();
        Setting::put('license_checked_at', now()->toDateTimeString());
        AuditLog::record('license', 'Saved + validated license key: ' . $result['state']);

        return back()->with('status', self::resultMessage($result));
    }

    /** Re-validate the stored key online against scriptgain.com (/v1/validate, RSA-signed). */
    public function sync(Request $request)
    {
        if (! LicenseClient::key()) {
            return back()->with('status', 'Enter a License Key first.');
        }

        $result = LicenseClient::refresh();
        Setting::put('license_checked_at', now()->toDateTimeString());
        AuditLog::record('license', 'Re-checked license: ' . $result['state']);

        return back()->with('status', self::resultMessage($result));
    }

    protected static function resultMessage(array $r): string
    {
        return match ($r['state']) {
            'valid' => 'License validated successfully.',
            'grace' => 'ScriptGain was unreachable, but your last check was valid — running on grace period.',
            'invalid' => 'License Key Invalid: ' . ($r['message'] ?? 'rejected by ScriptGain') . '.',
            'unverified' => 'Could not verify the license right now. ' . ($r['message'] ?? ''),
            'unlicensed' => 'No license key entered.',
            default => $r['message'] ?? 'License checked.',
        };
    }
}
