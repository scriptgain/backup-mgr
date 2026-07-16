<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BannedIp;
use App\Models\LoginAttempt;
use App\Models\Setting;
use App\Support\Firewall;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FirewallController extends Controller
{
    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Admins only.');
    }

    public function index(Request $request)
    {
        $this->ensureAdmin();

        $window = (int) Setting::get('lockout_minutes', 60);

        $sessions = DB::table('sessions')
            ->leftJoin('users', 'users.id', '=', 'sessions.user_id')
            ->select(
                'sessions.id',
                'sessions.user_id',
                'sessions.ip_address',
                'sessions.user_agent',
                'sessions.last_activity',
                'users.name as user_name',
                'users.email as user_email',
            )
            ->orderByDesc('sessions.last_activity')
            ->get();

        $bans = BannedIp::with('creator')->orderByDesc('created_at')->get();

        $attempts = LoginAttempt::query()
            ->where('successful', false)
            ->where('created_at', '>=', now()->subMinutes(max($window, 1)))
            ->selectRaw('ip, count(*) as attempts, max(created_at) as last_attempt, max(email) as email')
            ->groupBy('ip')
            ->orderByDesc('attempts')
            ->limit(50)
            ->get();

        $settings = [
            'failed_login_limit' => (int) Setting::get('failed_login_limit', 10),
            'lockout_minutes' => $window ?: 60,
            'access_limit_enabled' => Setting::get('access_limit_enabled') === '1',
            'ip_allowlist' => (string) Setting::get('ip_allowlist', ''),
        ];

        return view('settings.firewall', [
            'sessions' => $sessions,
            'bans' => $bans,
            'attempts' => $attempts,
            'settings' => $settings,
            'currentIp' => $request->ip(),
            'currentSessionId' => $request->session()->getId(),
        ]);
    }

    /** Manually ban an IP. Refuses to ban the admin's own current IP. */
    public function ban(Request $request)
    {
        $this->ensureAdmin();

        $data = $request->validate([
            'ip' => ['required', 'string', 'max:45'],
            'reason' => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        if (! filter_var($data['ip'], FILTER_VALIDATE_IP)) {
            return back()->withErrors(['ip' => 'Enter a valid IP address.'])->withInput();
        }

        if ($data['ip'] === $request->ip()) {
            return back()->withErrors(['ip' => 'You Cannot Ban Your Own Current IP Address.'])->withInput();
        }

        BannedIp::updateOrCreate(
            ['ip' => $data['ip']],
            [
                'reason' => $data['reason'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
                'created_by' => auth()->id(),
            ],
        );

        AuditLog::record('firewall_ban', 'Banned IP '.$data['ip'].($data['reason'] ? ' ('.$data['reason'].')' : ''));

        return back()->with('status', 'IP '.$data['ip'].' has been banned.');
    }

    public function unban(BannedIp $bannedIp)
    {
        $this->ensureAdmin();

        $ip = $bannedIp->ip;
        $bannedIp->delete();

        AuditLog::record('firewall_unban', 'Unbanned IP '.$ip);

        return back()->with('status', 'IP '.$ip.' has been unbanned.');
    }

    /** Revoke a database session. Refuses to revoke the admin's own session. */
    public function revokeSession(Request $request, string $id)
    {
        $this->ensureAdmin();

        if ($id === $request->session()->getId()) {
            return back()->with('warning', 'You cannot revoke your own current session.');
        }

        DB::table('sessions')->where('id', $id)->delete();

        AuditLog::record('firewall_session_revoke', 'Revoked session '.substr($id, 0, 8).'...');

        return back()->with('status', 'Session revoked.');
    }

    /** Save failed-login protection and access-limit settings with safety rails. */
    public function update(Request $request)
    {
        $this->ensureAdmin();

        $data = $request->validate([
            'failed_login_limit' => ['required', 'integer', 'min:1', 'max:1000'],
            'lockout_minutes' => ['required', 'integer', 'min:1', 'max:100000'],
            'ip_allowlist' => ['nullable', 'string', 'max:10000'],
        ]);

        $enabled = $request->boolean('access_limit_enabled');
        $entries = Firewall::parse($data['ip_allowlist'] ?? '');

        foreach ($entries as $entry) {
            if (! Firewall::validEntry($entry)) {
                return back()->withErrors(['ip_allowlist' => "Not a valid IP or CIDR range: {$entry}"])->withInput();
            }
        }

        // Safety rails: enabling the allowlist must never lock out the current
        // admin, and must never be enabled against an empty list.
        if ($enabled) {
            $currentIp = $request->ip();
            if ($currentIp && ! Firewall::ipAllowed($currentIp, $entries)) {
                $entries[] = $currentIp;
            }
            if (empty($entries)) {
                return back()
                    ->withErrors(['ip_allowlist' => 'Add at least one allowed IP before enabling the access limit.'])
                    ->withInput();
            }
        }

        Setting::put('failed_login_limit', (string) $data['failed_login_limit']);
        Setting::put('lockout_minutes', (string) $data['lockout_minutes']);
        Setting::put('ip_allowlist', implode("\n", $entries));
        Setting::put('access_limit_enabled', $enabled ? '1' : '0');

        AuditLog::record('firewall_settings', 'Updated firewall settings (access limit '.($enabled ? 'enabled' : 'disabled').').');

        return back()->with('status', 'Firewall settings saved.');
    }
}
