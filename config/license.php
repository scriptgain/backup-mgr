<?php

/*
|--------------------------------------------------------------------------
| BackupMGR Licensing
|--------------------------------------------------------------------------
| Self-hosted installs validate their license against scriptgain.com (the
| software vendor). Responses are RSA-signed; the embedded public key below
| lets this install verify a response was not forged in transit.
|
| Enforcement is intentionally lenient: a failed network check falls back to
| the last good result within the grace window, and the license NEVER hard
| locks the panel (this is a backup product; locking the operator out could
| block a restore). Invalid/expired shows a persistent banner instead.
*/

return [
    // scriptgain licensing API base (no trailing slash).
    'endpoint' => env('LICENSE_ENDPOINT', 'https://scriptgain.com/v1'),

    // The vendor product this build licenses against.
    'product' => env('LICENSE_PRODUCT', 'backup-manager'),

    // The compiled license-enforcement helper. When present + executable, the RSA
    // signature verification runs in this binary (unpatchable) instead of inline
    // PHP; when absent, the PHP openssl_verify path is used (fail-soft).
    'guard_binary' => env('LICENSE_GUARD_BINARY', base_path('bin/licenseguard')),

    // Expected sha256 of the trusted guard binary (anti-tamper LAYER 2). PHP hashes
    // the on-disk binary and rejects a swapped one before trusting it. Empty
    // disables the check. Update on every rebuild.
    'guard_sha256' => env('LICENSE_GUARD_SHA256', '7593ce44cff3194003c7774b7e12adee49fe954f553840bf3adc69e64a34396a'),

    // Days a previously-valid license keeps working if the endpoint is
    // unreachable, before the banner flips to "cannot verify".
    'grace_days' => (int) env('LICENSE_GRACE_DAYS', 14),

    // How often (minutes) to re-validate online. Cached between checks.
    'check_every_minutes' => (int) env('LICENSE_CHECK_MINUTES', 720),

    // scriptgain.com RSA-2048 public key. Used to verify signed responses.
    'public_key' => <<<'PEM'
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAzFrRFiXb2ClbB+YDkOTj
vwMwJCZ1hC65IJ2rbLNM2zdUzMB/eT/MJ7iL5fFEWFCKytAoAuLr0Gofx2CE3u7y
WILwb+ZUT2eFNctFrWJiL737Cgh3Dx1tQmkveVZvs8elvZ+Kh2Gh8tEbKZ7pW+pl
dZwlHY4gBo3+YiAaYns9mcZuHDNO7Dm6Vn8B3hxYMzJ6lr/qoH/f+ZiT67Lcjzsl
O64X+7D4A0nBGBOVk6h0n8ZkoToXply6Qe0tUz8YWcJ4VJkAnFNlaDPDAl+E4EmL
B8CwKpuG6rsQaopXKP2K+XGXge9oOB25RCTKcQyB0hOqeu61pxwquUkC/iVyxPzH
jwIDAQAB
-----END PUBLIC KEY-----
PEM,
];
