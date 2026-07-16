<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Validates this self-hosted BackupMGR install against scriptgain.com.
 *
 * States returned by status():
 *   valid       - license active, response signature verified
 *   invalid     - endpoint says not valid (expired/suspended/revoked/not_found)
 *   grace       - endpoint unreachable but last check was valid within grace window
 *   unverified  - unreachable beyond grace, or a signature that failed verification
 *   unlicensed  - no key entered yet
 *
 * By design this never throws to callers and never hard-locks the panel.
 */
class LicenseClient
{
    /** Cached, throttled status for the whole app. */
    public static function status(): array
    {
        return Cache::remember('license.status', now()->addMinutes((int) config('license.check_every_minutes', 720)), function () {
            return self::check();
        });
    }

    /** Force a fresh online check (used by the admin "Re-check" action / command). */
    public static function refresh(): array
    {
        Cache::forget('license.status');
        $status = self::check();
        Cache::put('license.status', $status, now()->addMinutes((int) config('license.check_every_minutes', 720)));

        return $status;
    }

    public static function key(): ?string
    {
        return Setting::get('license_key');
    }

    /** Stable per-install fingerprint so seat counting is consistent. */
    public static function deviceId(): string
    {
        $id = Setting::get('license_device_id');
        if (! $id) {
            $id = (string) Str::uuid();
            Setting::put('license_device_id', $id);
        }

        return $id;
    }

    protected static function check(): array
    {
        $key = self::key();
        if (! $key) {
            return self::result('unlicensed', null, 'No license key entered.');
        }

        $endpoint = rtrim((string) config('license.endpoint'), '/');

        try {
            $resp = Http::timeout(8)->acceptJson()->asJson()->post($endpoint . '/validate', [
                'key' => $key,
                'product' => config('license.product'),
                'device' => self::deviceId(),
                'hostname' => gethostname() ?: parse_url((string) config('app.url'), PHP_URL_HOST),
            ]);
        } catch (\Throwable $e) {
            return self::offlineFallback('Endpoint unreachable: ' . $e->getMessage());
        }

        if (! $resp->successful()) {
            return self::offlineFallback('Endpoint returned HTTP ' . $resp->status());
        }

        $body = $resp->json();
        $payload = $body['response'] ?? null;
        $signature = $body['signature'] ?? null;

        if (! is_array($payload) || ! is_string($signature)) {
            return self::offlineFallback('Malformed license response.');
        }

        if (! self::verifySignature($payload, $signature)) {
            // A response that will not verify is treated as untrusted, not valid.
            return self::result('unverified', $payload, 'License response failed signature verification.');
        }

        if (! empty($payload['valid'])) {
            Setting::put('license_last_valid_at', now()->toIso8601String());
            Setting::put('license_last_response', json_encode($payload));

            return self::result('valid', $payload, 'License active.');
        }

        return self::result('invalid', $payload, 'License not valid: ' . ($payload['reason'] ?? 'unknown') . '.');
    }

    /** Use the last known-good result if we are still inside the grace window. */
    protected static function offlineFallback(string $why): array
    {
        $lastAt = Setting::get('license_last_valid_at');
        $graceDays = (int) config('license.grace_days', 14);

        if ($lastAt && Carbon::parse($lastAt)->addDays($graceDays)->isFuture()) {
            $payload = json_decode((string) Setting::get('license_last_response'), true) ?: null;

            return self::result('grace', $payload, 'Cannot reach license server; running on grace period. ' . $why);
        }

        return self::result('unverified', null, 'Cannot verify license and grace period has ended. ' . $why);
    }

    /**
     * Verify an RSA-SHA256 signature over the canonical JSON of the payload.
     * Must mirror scriptgain's LicenseSigner::canonical() exactly:
     * top-level ksort, then json_encode with unescaped slashes.
     */
    public static function verifySignature(array $payload, string $signatureB64): bool
    {
        ksort($payload);
        $data = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $pub = (string) config('license.public_key');

        return openssl_verify($data, base64_decode($signatureB64), $pub, OPENSSL_ALGO_SHA256) === 1;
    }

    protected static function result(string $state, ?array $license, string $message): array
    {
        return [
            'state' => $state,
            'ok' => in_array($state, ['valid', 'grace'], true),
            'license' => $license,
            'message' => $message,
            'checked_at' => now()->toIso8601String(),
        ];
    }
}
