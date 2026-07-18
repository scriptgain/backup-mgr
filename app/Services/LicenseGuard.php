<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * Compiled license-enforcement front-end.
 *
 * The security-critical part of licensing — verifying that a license blob was
 * genuinely signed by ScriptGain, and turning it into a valid/expired/stale/…
 * verdict — is trivial to patch out when it lives in source-available PHP (flip
 * one `openssl_verify(...) === 1`). This service hands that decision to a tiny
 * statically-linked Go helper (`bin/licenseguard`) that embeds ScriptGain's RSA
 * *public* key. Without the vendor private key nobody can forge a blob the helper
 * reports as valid, so the check can no longer be bypassed by editing PHP.
 *
 * Operational safety: if the binary is missing or not runnable (not yet
 * installed, wrong arch), every method returns null / false-with-fallback so the
 * CALLER falls back to the existing inline PHP verification. The app therefore
 * never breaks because the helper is absent — production should ship the binary
 * to make the check unpatchable, but its absence is fail-soft, never fail-closed.
 *
 * The verdict is cached (~10 min) keyed on the exact bytes, so the helper is
 * exec'd at most a handful of times an hour rather than every request.
 */
class LicenseGuard
{
    /** How long a verdict for a given (canonical, signature) pair is cached. */
    private const CACHE_TTL = 600;

    /** Absolute path to the compiled helper. */
    public static function binaryPath(): string
    {
        return (string) config('license.guard_binary', base_path('bin/licenseguard'));
    }

    /** True when the helper exists and is executable on this host. */
    public static function available(): bool
    {
        $bin = self::binaryPath();

        return $bin !== '' && is_file($bin) && is_executable($bin);
    }

    /**
     * Verify a signature over the given canonical bytes and evaluate the license
     * state, via the compiled helper.
     *
     * @return array{valid:bool,reason:string,state:string,signature_valid:bool,product:string,product_match:bool,expires_at:string,offline_expires_at:string,issued_at:string,grace:bool,entitlements:mixed,checked_at:int}|null
     *              The helper's verdict, or NULL when the helper is unavailable or
     *              returned something unusable — the caller must then fall back to
     *              its own PHP verification.
     */
    public static function evaluate(string $canonical, string $signatureB64, ?string $product = null): ?array
    {
        if ($canonical === '' || $signatureB64 === '') {
            return null;
        }

        if (! self::available()) {
            self::warnMissingOnce();

            return null;
        }

        $key = 'licenseguard:'.sha1($canonical.'|'.$signatureB64.'|'.(string) $product);

        $verdict = Cache::remember($key, self::CACHE_TTL, function () use ($canonical, $signatureB64, $product) {
            return self::exec($canonical, $signatureB64, $product);
        });

        // Never cache a failed exec (so a transient failure doesn't stick).
        if (! is_array($verdict)) {
            Cache::forget($key);

            return null;
        }

        return $verdict;
    }

    /**
     * Convenience for callers that only need the signature answer (e.g. verifying
     * a signed online /v1/validate response). Returns true/false when the helper
     * gave a definitive answer, or NULL when it was unavailable (fall back to PHP).
     */
    public static function signatureValid(string $canonical, string $signatureB64, ?string $product = null): ?bool
    {
        $v = self::evaluate($canonical, $signatureB64, $product);
        if ($v === null) {
            return null;
        }

        return (bool) ($v['signature_valid'] ?? false);
    }

    /** Run the helper once. Returns the decoded verdict or null on any failure. */
    private static function exec(string $canonical, string $signatureB64, ?string $product): ?array
    {
        $cmd = [self::binaryPath()];
        if ($product !== null && $product !== '') {
            $cmd[] = '--product';
            $cmd[] = $product;
        }

        try {
            $result = Process::timeout(10)
                ->input(json_encode(['canonical' => $canonical, 'signature' => $signatureB64]))
                ->run($cmd);
        } catch (\Throwable $e) {
            Log::warning('LicenseGuard: helper exec failed, falling back to PHP verification', ['error' => $e->getMessage()]);

            return null;
        }

        // The helper always exits 0; a non-zero exit means it never ran (e.g.
        // exec denied). Treat as unavailable -> PHP fallback.
        if (! $result->successful()) {
            Log::warning('LicenseGuard: helper returned non-zero exit, falling back to PHP verification', [
                'exit' => $result->exitCode(),
                'stderr' => trim($result->errorOutput()),
            ]);

            return null;
        }

        $verdict = json_decode(trim($result->output()), true);
        if (! is_array($verdict) || ! isset($verdict['state'])) {
            Log::warning('LicenseGuard: helper produced unreadable verdict, falling back to PHP verification');

            return null;
        }

        return $verdict;
    }

    /** Warn (at most once per hour) that the compiled guard is not installed. */
    private static function warnMissingOnce(): void
    {
        Cache::remember('licenseguard:missing-warned', 3600, function () {
            Log::warning('LicenseGuard: compiled helper not found at '.self::binaryPath().'; using PHP verification fallback. Ship the binary in production to make the license check unpatchable.');

            return true;
        });
    }
}
