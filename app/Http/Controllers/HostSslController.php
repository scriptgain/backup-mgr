<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Services\SslManager;
use Illuminate\Http\Request;

/**
 * Host / Domain / SSL manager (Settings area). Admin only.
 *
 * Lets an admin point a fresh IP-only install at a real hostname and obtain a
 * trusted certificate via Let's Encrypt, an uploaded cert, or a self-signed one.
 */
class HostSslController extends Controller
{
    public function __construct(private SslManager $ssl)
    {
    }

    private function guard(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    public function edit(Request $request)
    {
        $this->guard();

        // Detect a pre-existing, externally-issued certificate. If a valid,
        // browser-trusted cert is already served for the host and we have no
        // record of issuing one ourselves, we treat SSL as externally managed
        // and hide the issue/overwrite controls. Stays conservative: any
        // uncertainty (probe failed, self-signed, expired, or we did issue)
        // falls back to the normal management UI.
        $managed = $this->ssl->isManagedByUs();
        $serving = $this->ssl->detectServingCertificate($request->getHost());
        $unmanagedDetected = ! $managed
            && $serving !== null
            && $serving['valid']
            && ! $serving['self_signed'];

        return view('settings.host', [
            'c'        => $this->ssl->config(),
            'status'   => $this->ssl->certificateStatus(),
            'acme'     => $this->ssl->acmeBinary(),
            'currentHost' => $request->getHost(),
            'currentUrl'  => $request->getSchemeAndHttpHost(),
            'serverIp'    => $request->server('SERVER_ADDR') ?: gethostbyname(gethostname()),
            'serving'     => $serving,
            'unmanagedDetected' => $unmanagedDetected,
        ]);
    }

    public function update(Request $request)
    {
        $this->guard();

        $data = $request->validate([
            'app_hostname'   => ['nullable', 'string', 'max:253'],
            'ssl_le_email'   => ['nullable', 'email', 'max:191'],
            'ssl_webroot'    => ['nullable', 'string', 'max:255'],
            'ssl_cert_path'  => ['nullable', 'string', 'max:255'],
            'ssl_key_path'   => ['nullable', 'string', 'max:255'],
            'ssl_reload_cmd' => ['nullable', 'string', 'max:500'],
        ]);

        if (! empty($data['app_hostname']) && ! SslManager::isValidHostname($data['app_hostname'])) {
            return back()->withErrors(['app_hostname' => 'Enter a valid hostname such as app.example.com.'])->withInput();
        }

        foreach ($data as $key => $value) {
            Setting::put($key, (string) ($value ?? ''));
        }

        AuditLog::record('updated', 'Host and SSL settings updated');

        return redirect()->route('settings.host.edit')->with('status', 'Host settings saved.');
    }

    public function letsencrypt(Request $request)
    {
        $this->guard();

        $result = $this->ssl->issueLetsEncrypt();
        $this->recordRun('letsencrypt', $result);

        return redirect()->route('settings.host.edit')
            ->with($result['ok'] ? 'status' : 'warning',
                $result['ok'] ? 'Let\'s Encrypt certificate issued and installed.' : 'Certificate request did not complete. See the log below.');
    }

    public function upload(Request $request)
    {
        $this->guard();

        $data = $request->validate([
            'certificate' => ['required', 'string'],
            'private_key' => ['required', 'string'],
        ]);

        $result = $this->ssl->installUploaded($data['certificate'], $data['private_key']);
        $this->recordRun('upload', $result);

        return redirect()->route('settings.host.edit')
            ->with($result['ok'] ? 'status' : 'warning',
                $result['ok'] ? 'Certificate installed.' : 'Upload rejected: '.$result['output']);
    }

    public function selfSigned(Request $request)
    {
        $this->guard();

        $result = $this->ssl->generateSelfSigned();
        $this->recordRun('selfsigned', $result);

        return redirect()->route('settings.host.edit')
            ->with($result['ok'] ? 'status' : 'warning',
                $result['ok'] ? 'Self-signed certificate generated.' : 'Could not generate a self-signed certificate.');
    }

    private function recordRun(string $mode, array $result): void
    {
        Setting::put('ssl_mode', $mode);
        Setting::put('ssl_last_action', $mode);
        Setting::put('ssl_last_output', $result['output'] ?? '');
        Setting::put('ssl_last_at', now()->toDateTimeString());
        AuditLog::record($result['ok'] ? 'updated' : 'error',
            'SSL '.$mode.': '.($result['ok'] ? 'success' : 'failed'));
    }
}
