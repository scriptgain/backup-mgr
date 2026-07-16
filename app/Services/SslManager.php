<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Process;

/**
 * Host / domain / SSL manager for self-hosted installs.
 *
 * A fresh install is usually reached by IP with a self-signed cert. This helper
 * lets an admin set the real hostname and obtain a browser-trusted certificate
 * three ways: Let's Encrypt (acme.sh, HTTP-01), uploading their own cert + key,
 * or generating a self-signed cert as a last resort.
 *
 * Paths and the webserver reload command are configurable (Setting keys) so the
 * feature works regardless of how the operator's web server is wired. Defaults
 * write into storage/app/ssl so the app can always persist a cert even without
 * privilege; the reload command is what points the live server at those files.
 */
class SslManager
{
    /** Setting keys with their defaults (resolved lazily so paths use runtime storage_path). */
    public function config(): array
    {
        return [
            'app_hostname'   => Setting::get('app_hostname', ''),
            'ssl_le_email'   => Setting::get('ssl_le_email', ''),
            'ssl_webroot'    => Setting::get('ssl_webroot', public_path()),
            'ssl_cert_path'  => Setting::get('ssl_cert_path', storage_path('app/ssl/fullchain.pem')),
            'ssl_key_path'   => Setting::get('ssl_key_path', storage_path('app/ssl/private.key')),
            'ssl_reload_cmd' => Setting::get('ssl_reload_cmd', ''),
            'ssl_mode'       => Setting::get('ssl_mode', ''),
            'ssl_last_action' => Setting::get('ssl_last_action', ''),
            'ssl_last_output' => Setting::get('ssl_last_output', ''),
            'ssl_last_at'    => Setting::get('ssl_last_at', ''),
        ];
    }

    /** A syntactically valid DNS hostname (no scheme, no path). */
    public static function isValidHostname(string $host): bool
    {
        return (bool) preg_match('/^(?=.{1,253}$)([a-z0-9](-?[a-z0-9])*)(\.[a-z0-9](-?[a-z0-9])*)+$/i', $host);
    }

    /**
     * Inspect the certificate currently on disk at ssl_cert_path.
     * Returns null when no cert is present.
     */
    public function certificateStatus(): ?array
    {
        $path = $this->config()['ssl_cert_path'];
        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $pem = file_get_contents($path);
        $cert = @openssl_x509_parse($pem);
        if (! $cert) {
            return null;
        }

        $notAfter = isset($cert['validTo_time_t']) ? (int) $cert['validTo_time_t'] : null;
        $daysLeft = $notAfter ? (int) floor(($notAfter - time()) / 86400) : null;
        $subjectCn = $cert['subject']['CN'] ?? '';
        $issuerCn = $cert['issuer']['CN'] ?? '';

        return [
            'subject'    => $subjectCn,
            'issuer'     => $issuerCn,
            'self_signed' => $subjectCn !== '' && $subjectCn === $issuerCn,
            'expires_at' => $notAfter ? date('Y-m-d', $notAfter) : null,
            'days_left'  => $daysLeft,
            'expired'    => $daysLeft !== null && $daysLeft < 0,
        ];
    }

    /**
     * True when a certificate was issued/installed through this panel. We record
     * an issuance mode (letsencrypt / upload / selfsigned) whenever we act, so an
     * empty mode means we have never managed a cert for this install.
     */
    public function isManagedByUs(): bool
    {
        return trim((string) Setting::get('ssl_mode', '')) !== '';
    }

    /**
     * Probe the certificate actually served over TLS for a hostname (the live
     * cert a browser would see, which may be issued by an external stack such as
     * the host panel rather than by us). Returns null when it cannot be read.
     *
     * Conservative by design: any connection/parse failure returns null so the
     * caller falls back to the normal management UI rather than hiding it.
     *
     * @return array{subject:string,issuer:string,issuer_org:string,expires_at:?string,self_signed:bool,valid:bool}|null
     */
    public function detectServingCertificate(?string $host, int $port = 443): ?array
    {
        $host = trim((string) $host);
        if ($host === '' || ! self::isValidHostname($host)) {
            return null;
        }

        $ctx = stream_context_create(['ssl' => [
            'capture_peer_cert' => true,
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'SNI_enabled'       => true,
            'peer_name'         => $host,
        ]]);

        $client = @stream_socket_client(
            "ssl://{$host}:{$port}", $errno, $errstr, 4,
            STREAM_CLIENT_CONNECT, $ctx
        );
        if (! $client) {
            return null;
        }

        $params = @stream_context_get_params($client);
        @fclose($client);

        $peer = $params['options']['ssl']['peer_certificate'] ?? null;
        if (! $peer) {
            return null;
        }

        $cert = @openssl_x509_parse($peer);
        if (! $cert) {
            return null;
        }

        $now       = time();
        $notAfter  = isset($cert['validTo_time_t']) ? (int) $cert['validTo_time_t'] : null;
        $notBefore = isset($cert['validFrom_time_t']) ? (int) $cert['validFrom_time_t'] : null;
        $subjectCn = $cert['subject']['CN'] ?? '';
        $issuerCn  = $cert['issuer']['CN'] ?? '';
        $issuerOrg = $cert['issuer']['O'] ?? '';

        return [
            'subject'     => $subjectCn,
            'issuer'      => $issuerCn ?: $issuerOrg,
            'issuer_org'  => $issuerOrg,
            'expires_at'  => $notAfter ? date('Y-m-d', $notAfter) : null,
            'self_signed' => $subjectCn !== '' && $subjectCn === $issuerCn,
            'valid'       => $notAfter !== null && $notAfter > $now
                             && ($notBefore === null || $notBefore <= $now),
        ];
    }

    /** Locate an acme.sh binary, or null if none is installed for this user. */
    public function acmeBinary(): ?string
    {
        $candidates = array_filter([
            getenv('HOME') ? getenv('HOME').'/.acme.sh/acme.sh' : null,
            '/root/.acme.sh/acme.sh',
        ]);
        foreach ($candidates as $c) {
            if (is_file($c) && is_executable($c)) {
                return $c;
            }
        }
        $which = Process::run(['which', 'acme.sh']);
        if ($which->successful() && trim($which->output()) !== '') {
            return trim($which->output());
        }

        return null;
    }

    /**
     * Issue / renew a Let's Encrypt certificate via acme.sh HTTP-01 and install
     * it to the configured cert/key paths, then run the reload command.
     *
     * @return array{ok: bool, output: string}
     */
    public function issueLetsEncrypt(): array
    {
        $c = $this->config();
        $host = $c['app_hostname'];

        if (! self::isValidHostname($host)) {
            return ['ok' => false, 'output' => 'Set a valid hostname before issuing a certificate.'];
        }
        $acme = $this->acmeBinary();
        if (! $acme) {
            return ['ok' => false, 'output' =>
                "acme.sh is not installed for this account.\nInstall it once over SSH as the site user:\n".
                "  curl https://get.acme.sh | sh -s email={$c['ssl_le_email']}\n".
                'then try again.'];
        }

        $this->ensureDir($c['ssl_cert_path']);
        $this->ensureDir($c['ssl_key_path']);

        $log = '';

        if ($c['ssl_le_email'] !== '') {
            $reg = Process::timeout(120)->run([$acme, '--register-account', '-m', $c['ssl_le_email'], '--server', 'letsencrypt']);
            $log .= $this->tail($reg->output().$reg->errorOutput());
        }

        $issue = Process::timeout(300)->run([
            $acme, '--issue', '-d', $host, '-w', $c['ssl_webroot'],
            '--server', 'letsencrypt', '--keylength', '2048',
        ]);
        $log .= "\n$ acme.sh --issue -d {$host}\n".$this->tail($issue->output().$issue->errorOutput());

        // acme.sh returns non-zero when the cert is still valid ("Domains not changed").
        $alreadyValid = str_contains($issue->output().$issue->errorOutput(), 'Domains not changed')
            || str_contains($issue->output().$issue->errorOutput(), 'Skipping');
        if (! $issue->successful() && ! $alreadyValid) {
            return ['ok' => false, 'output' => trim($log)];
        }

        $install = Process::timeout(120)->run(array_filter([
            $acme, '--install-cert', '-d', $host,
            '--key-file', $c['ssl_key_path'],
            '--fullchain-file', $c['ssl_cert_path'],
            $c['ssl_reload_cmd'] !== '' ? '--reloadcmd' : null,
            $c['ssl_reload_cmd'] !== '' ? $c['ssl_reload_cmd'] : null,
        ]));
        $log .= "\n$ acme.sh --install-cert -d {$host}\n".$this->tail($install->output().$install->errorOutput());

        return ['ok' => $install->successful(), 'output' => trim($log)];
    }

    /**
     * Validate and install an operator-supplied certificate + private key.
     *
     * @return array{ok: bool, output: string}
     */
    public function installUploaded(string $certPem, string $keyPem): array
    {
        $certPem = trim($certPem);
        $keyPem = trim($keyPem);

        if (! @openssl_x509_read($certPem)) {
            return ['ok' => false, 'output' => 'The certificate is not valid PEM.'];
        }
        $key = @openssl_pkey_get_private($keyPem);
        if (! $key) {
            return ['ok' => false, 'output' => 'The private key is not valid PEM.'];
        }
        if (! openssl_x509_check_private_key($certPem, $keyPem)) {
            return ['ok' => false, 'output' => 'The private key does not match the certificate.'];
        }

        $c = $this->config();
        $this->writeSecure($c['ssl_cert_path'], $certPem."\n");
        $this->writeSecure($c['ssl_key_path'], $keyPem."\n");

        $reload = $this->reload();

        return ['ok' => true, 'output' => trim("Certificate installed.\n".$reload)];
    }

    /**
     * Generate a self-signed certificate for the configured hostname.
     *
     * @return array{ok: bool, output: string}
     */
    public function generateSelfSigned(): array
    {
        $c = $this->config();
        $host = $c['app_hostname'] !== '' ? $c['app_hostname'] : (request()->getHost() ?: 'localhost');

        $dn = ['commonName' => substr($host, 0, 64)];
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        if (! $key) {
            return ['ok' => false, 'output' => 'Could not generate a key pair (openssl unavailable).'];
        }
        $csr = openssl_csr_new($dn, $key, ['digest_alg' => 'sha256']);
        $conf = ['digest_alg' => 'sha256', 'x509_extensions' => 'v3_req'];
        $cert = openssl_csr_sign($csr, null, $key, 365, $conf);

        openssl_x509_export($cert, $certOut);
        openssl_pkey_export($key, $keyOut);

        $this->writeSecure($c['ssl_cert_path'], $certOut);
        $this->writeSecure($c['ssl_key_path'], $keyOut);

        $reload = $this->reload();

        return ['ok' => true, 'output' => trim("Self-signed certificate generated for {$host}.\n".$reload)];
    }

    /** Run the configured webserver reload command, if any. */
    public function reload(): string
    {
        $cmd = $this->config()['ssl_reload_cmd'];
        if (trim($cmd) === '') {
            return 'No reload command configured; point your web server at the cert files manually.';
        }
        $r = Process::timeout(60)->run($cmd);

        return "Reload: ".($r->successful() ? 'ok' : 'failed')."\n".$this->tail($r->output().$r->errorOutput());
    }

    private function ensureDir(string $file): void
    {
        $dir = dirname($file);
        if (! is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
    }

    private function writeSecure(string $path, string $contents): void
    {
        $this->ensureDir($path);
        file_put_contents($path, $contents);
        @chmod($path, 0640);
    }

    /** Keep captured command output to a sane length for the settings UI. */
    private function tail(string $s, int $max = 4000): string
    {
        $s = trim($s);
        return strlen($s) > $max ? '…'.substr($s, -$max) : $s;
    }
}
