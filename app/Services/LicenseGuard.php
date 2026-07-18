<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * Compiled, anti-tamper license-enforcement front-end.
 *
 * The security-critical part of licensing — verifying that a license blob was
 * genuinely signed by ScriptGain, and turning it into a valid/expired/stale/…
 * verdict — is trivial to patch out when it lives in source-available PHP (flip
 * one `openssl_verify(...) === 1`). This service hands that decision to a tiny
 * statically-linked Go helper (`bin/licenseguard`) that embeds ScriptGain's RSA
 * *public* key, and layers three anti-tamper defences on top so that simply
 * swapping the binary or patching one PHP line is not enough:
 *
 *   LAYER 1 — Nonce-bound, vendor-signed attestation.
 *     PHP sends a fresh random nonce to the guard. The guard echoes the nonce and
 *     returns the exact vendor-signed canonical payload + signature it verified.
 *     PHP then re-verifies that vendor signature itself and checks the nonce. A
 *     fake "always valid" stub cannot produce a vendor-signed payload (no private
 *     key) and cannot precompute the nonce, so a naive binary swap is rejected.
 *
 *   LAYER 2 — Binary integrity check, vendor-signed.
 *     Before trusting a verdict, PHP hashes the on-disk binary and compares it to
 *     the expected sha256 read from a VENDOR-SIGNED release manifest
 *     ({version, sha256} + RSA-SHA256 signature), verified against the embedded
 *     public key. A customer cannot forge that signature, so they cannot edit the
 *     expected hash to match a swapped binary. config('license.guard_sha256')
 *     remains only as a last-resort, patchable baseline for a first run with no
 *     manifest. A swapped binary changes the hash and is rejected. The guard also
 *     self-reports its hash + version for cross-check.
 *
 *   LAYER 3 — Periodic online re-validation (the real backstop).
 *     Handled by OnlineLicenseCheck (scaffold) / LicenseClient (backup): a
 *     scheduled call to ScriptGain's /v1/validate confirms the key is active and
 *     not revoked, cached hours with an offline grace window. That signature
 *     verification is ALSO routed through this guard. Revoking a key kills a
 *     cloned/cracked install at its next online check — the one thing a
 *     client-side attacker with root cannot fake.
 *
 * Honest posture: layers 1 and 2 are themselves PHP and thus patchable — that is
 * why they are layered, and why bypass requires defeating compiled machine code
 * AND forging a vendor signature AND evading the online check simultaneously
 * (ionCube-class deterrent). A determined attacker with root can still win on the
 * client; the online-revalidation + revocation path in layer 3 is the durable
 * enforcement.
 *
 * Operational safety: any failure of the guard (missing binary, wrong arch, hash
 * mismatch, failed attestation) returns null / false-with-fallback so the CALLER
 * falls back to the existing inline PHP verification. The app therefore never
 * breaks — a bad or absent guard degrades to today's behaviour, never a lockout.
 */
class LicenseGuard
{
    /** How long a trusted verdict for a given (canonical, signature) pair is cached. */
    private const CACHE_TTL = 600;

    /**
     * ScriptGain license-signing public key (identical across the fleet). Used for
     * the LAYER 1 re-verification of the vendor-signed payload the guard returns.
     */
    private const PUBLIC_KEY = <<<'PEM'
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAzFrRFiXb2ClbB+YDkOTj
vwMwJCZ1hC65IJ2rbLNM2zdUzMB/eT/MJ7iL5fFEWFCKytAoAuLr0Gofx2CE3u7y
WILwb+ZUT2eFNctFrWJiL737Cgh3Dx1tQmkveVZvs8elvZ+Kh2Gh8tEbKZ7pW+pl
dZwlHY4gBo3+YiAaYns9mcZuHDNO7Dm6Vn8B3hxYMzJ6lr/qoH/f+ZiT67Lcjzsl
O64X+7D4A0nBGBOVk6h0n8ZkoToXply6Qe0tUz8YWcJ4VJkAnFNlaDPDAl+E4EmL
B8CwKpuG6rsQaopXKP2K+XGXge9oOB25RCTKcQyB0hOqeu61pxwquUkC/iVyxPzH
jwIDAQAB
-----END PUBLIC KEY-----
PEM;

    /** Absolute path to the compiled helper. */
    public static function binaryPath(): string
    {
        return (string) config('license.guard_binary', base_path('bin/licenseguard'));
    }

    /** Absolute path to the vendor-signed release manifest for the helper. */
    public static function manifestPath(): string
    {
        return (string) config('license.guard_manifest', self::binaryPath().'.manifest.json');
    }

    /**
     * The expected sha256 of the trusted binary (LAYER 2), lowercased, or '' when
     * unknown. The TRUSTED source is the vendor-signed release manifest: a
     * {version, sha256} object plus an RSA-SHA256 signature over its canonical
     * form, verified here against the embedded public key. A customer cannot forge
     * that signature, so they cannot make the expected hash match a swapped binary.
     *
     * config('license.guard_sha256') is only a LAST-RESORT baseline for a first
     * run with no manifest present — it is intentionally weaker (patchable) and
     * used solely so the check can still function offline before a manifest ships.
     */
    public static function expectedSha256(): string
    {
        $signed = self::signedExpectedSha256();
        if ($signed !== null) {
            return $signed;
        }

        return strtolower(trim((string) config('license.guard_sha256', '')));
    }

    /**
     * The vendor-signed expected sha256 from the release manifest, or null when no
     * manifest is present or its signature does not verify against the embedded
     * public key (an unsigned/forged manifest is ignored, never trusted).
     */
    public static function signedExpectedSha256(): ?string
    {
        $path = self::manifestPath();
        if ($path === '' || ! is_file($path)) {
            return null;
        }

        $doc = json_decode((string) @file_get_contents($path), true);
        if (! is_array($doc) || ! isset($doc['manifest']) || ! is_array($doc['manifest']) || ! isset($doc['signature'])) {
            return null;
        }

        $manifest = $doc['manifest'];
        ksort($manifest); // canonical: top-level ksort + unescaped slashes (fleet-wide)
        $canonical = json_encode($manifest, JSON_UNESCAPED_SLASHES);

        $sig = base64_decode((string) $doc['signature'], true);
        $ok = $sig !== false
            && openssl_verify($canonical, $sig, self::PUBLIC_KEY, OPENSSL_ALGO_SHA256) === 1;

        if (! $ok) {
            self::warnOnce('manifest', 'guard release manifest signature did not verify; ignoring it (using config baseline)');

            return null;
        }

        $hash = strtolower(trim((string) ($doc['manifest']['sha256'] ?? '')));

        return $hash !== '' ? $hash : null;
    }

    /** True when the helper exists and is executable on this host. */
    public static function available(): bool
    {
        $bin = self::binaryPath();

        return $bin !== '' && is_file($bin) && is_executable($bin);
    }

    /**
     * LAYER 2: the on-disk binary's hash matches the expected value. Returns true
     * when no expected hash is configured (nothing to check) OR it matches; false
     * only on a definite mismatch (a swapped binary).
     */
    public static function integrityOk(): bool
    {
        $expected = self::expectedSha256();
        if ($expected === '') {
            return true; // not configured -> skip this layer (graceful)
        }

        $actual = @hash_file('sha256', self::binaryPath());

        return is_string($actual) && hash_equals($expected, strtolower($actual));
    }

    /**
     * Verify a signature over the given canonical bytes and evaluate the license
     * state, via the compiled helper, with all anti-tamper checks applied.
     *
     * @return array|null  The guard's trusted verdict, or NULL when the guard is
     *                     unavailable / failed integrity / failed attestation —
     *                     the caller must then fall back to its own PHP verification.
     */
    public static function evaluate(string $canonical, string $signatureB64, ?string $product = null): ?array
    {
        if ($canonical === '' || $signatureB64 === '') {
            return null;
        }

        if (! self::available()) {
            self::warnOnce('missing', 'compiled helper not found at '.self::binaryPath());

            return null;
        }

        $key = 'licenseguard:'.sha1($canonical.'|'.$signatureB64.'|'.(string) $product);

        $cached = Cache::get($key);
        if (is_array($cached)) {
            return $cached;
        }

        // LAYER 2: integrity check before we trust anything the binary says.
        if (! self::integrityOk()) {
            self::warnOnce('hash', 'binary sha256 does not match expected; treating guard as untrusted');

            return null;
        }

        $verdict = self::execAndAttest($canonical, $signatureB64, $product);
        if ($verdict === null) {
            return null; // attestation/exec failure -> caller falls back (not cached)
        }

        Cache::put($key, $verdict, self::CACHE_TTL);

        return $verdict;
    }

    /**
     * Convenience for callers that only need the signature answer (e.g. verifying
     * a signed online /v1/validate response). Returns true/false when the guard
     * gave a trusted answer, or NULL when unavailable/untrusted (fall back to PHP).
     */
    public static function signatureValid(string $canonical, string $signatureB64, ?string $product = null): ?bool
    {
        $v = self::evaluate($canonical, $signatureB64, $product);
        if ($v === null) {
            return null;
        }

        return (bool) ($v['signature_valid'] ?? false);
    }

    /**
     * Run the guard once with a fresh nonce and apply LAYER 1 attestation:
     *   - the nonce comes back verbatim (fresh, not a canned/cached stub reply),
     *   - the guard verified OUR canonical bytes (echoed back unchanged),
     *   - and when it claims a valid signature, that vendor signature really
     *     verifies against the embedded public key here in PHP too.
     * Any failure => null (untrusted) so the caller falls back to PHP verification.
     */
    private static function execAndAttest(string $canonical, string $signatureB64, ?string $product): ?array
    {
        $nonce = bin2hex(random_bytes(16));

        $cmd = [self::binaryPath()];
        if ($product !== null && $product !== '') {
            $cmd[] = '--product';
            $cmd[] = $product;
        }

        try {
            $result = Process::timeout(10)
                ->input(json_encode(['canonical' => $canonical, 'signature' => $signatureB64, 'nonce' => $nonce]))
                ->run($cmd);
        } catch (\Throwable $e) {
            Log::warning('LicenseGuard: helper exec failed, falling back to PHP verification', ['error' => $e->getMessage()]);

            return null;
        }

        if (! $result->successful()) {
            Log::warning('LicenseGuard: helper returned non-zero exit, falling back to PHP verification', [
                'exit' => $result->exitCode(), 'stderr' => trim($result->errorOutput()),
            ]);

            return null;
        }

        $verdict = json_decode(trim($result->output()), true);
        if (! is_array($verdict) || ! isset($verdict['state'])) {
            Log::warning('LicenseGuard: helper produced unreadable verdict, falling back to PHP verification');

            return null;
        }

        // LAYER 1a: nonce must round-trip (freshness / anti-canned-response).
        if (! isset($verdict['nonce']) || ! hash_equals($nonce, (string) $verdict['nonce'])) {
            self::warnOnce('nonce', 'guard did not echo the challenge nonce; treating as untrusted');

            return null;
        }

        // LAYER 1b: the guard must have verified the SAME bytes we handed it.
        if (($verdict['canonical'] ?? null) !== $canonical) {
            self::warnOnce('canonical', 'guard returned a different canonical payload; treating as untrusted');

            return null;
        }

        // LAYER 1c: if the guard claims the signature is valid, that vendor
        // signature must ALSO verify here against the embedded public key. A stub
        // that lies "valid" without a genuine vendor-signed payload fails this.
        if (! empty($verdict['signature_valid'])) {
            $sig = base64_decode((string) ($verdict['signature'] ?? ''), true);
            $ok = $sig !== false
                && openssl_verify($verdict['canonical'], $sig, self::PUBLIC_KEY, OPENSSL_ALGO_SHA256) === 1;

            if (! $ok) {
                self::warnOnce('forged', 'guard claimed a valid signature that does not verify; treating as untrusted (possible stub binary)');

                return null;
            }
        }

        return $verdict;
    }

    /** Log a given warning category at most once per hour to avoid log spam. */
    private static function warnOnce(string $tag, string $message): void
    {
        Cache::remember('licenseguard:warned:'.$tag, 3600, function () use ($message) {
            Log::warning('LicenseGuard: '.$message.'. Using PHP verification fallback. Ship a correct signed binary in production to keep the compiled check authoritative.');

            return true;
        });
    }
}
