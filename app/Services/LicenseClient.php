<?php

namespace App\Services;

use App\Models\Setting;
use App\Services\UpdateService;
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

        // Persist the exact signed bytes (canonical payload + signature) so the
        // compiled backup agents can re-verify the license against scriptgain's
        // key themselves, without trusting this source-available PHP layer. We
        // store it for verified valid AND invalid responses so a revocation
        // propagates to agents on the next heartbeat.
        Setting::put('license_signed', json_encode([
            'canonical' => self::canonical($payload),
            'signature' => $signature,
        ]));

        if (! empty($payload['valid'])) {
            Setting::put('license_last_valid_at', now()->toIso8601String());
            Setting::put('license_last_response', json_encode($payload));
            // Capture the signed version/download info for the self-updater.
            UpdateService::recordFromLicense($payload);

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
        $data = self::canonical($payload);

        // Prefer the compiled guard: it verifies the RSA signature against the
        // embedded ScriptGain key in code a customer cannot patch out. Falls back
        // to inline openssl_verify only when the binary is missing/not executable
        // (fail-soft — this is a backup product and must never break on a good
        // install). Hard enforcement of a bad license lives in the compiled agent,
        // which refuses to run backups; the panel/restore paths stay reachable.
        $guard = LicenseGuard::signatureValid($data, $signatureB64, config('license.product'));
        if ($guard !== null) {
            return $guard;
        }

        $pub = (string) config('license.public_key');

        return openssl_verify($data, base64_decode($signatureB64), $pub, OPENSSL_ALGO_SHA256) === 1;
    }

    /**
     * The exact byte string scriptgain signs: top-level ksort, then json_encode
     * with unescaped slashes. Nested objects keep their received order. Both this
     * PHP layer and the Go agent verify signatures over these bytes verbatim, so
     * neither side has to reproduce the other's JSON encoder.
     */
    public static function canonical(array $payload): string
    {
        ksort($payload);

        return json_encode($payload, JSON_UNESCAPED_SLASHES);
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
