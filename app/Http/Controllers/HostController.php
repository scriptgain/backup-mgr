<?php

namespace App\Http\Controllers;

use App\Models\Director;
use App\Models\Host;
use App\Models\Repository;
use App\Models\Run;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class HostController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $hosts = Host::visibleTo($user)
            ->with('director:id,name', 'owner:id,name')->latest()->get();
        $directors = Director::visibleTo($user)->orderBy('name')->get();

        return view('hosts.index', compact('hosts', 'directors'));
    }

    private function guardDirector(Director $director): void
    {
        abort_unless(auth()->user()->isAdmin() || $director->user_id === auth()->id(), 403);
    }

    private function guard(Host $host): void
    {
        abort_unless($host->isVisibleTo(auth()->user()), 403);
    }

    /** Users an admin may assign as a host owner. Non-admins get an empty list. */
    private function assignableOwners()
    {
        return auth()->user()->isAdmin()
            ? \App\Models\User::orderBy('name')->get(['id', 'name', 'email'])
            : collect();
    }

    public function create(Director $director)
    {
        $this->guardDirector($director);
        $scheduleTemplates = \App\Models\ScheduleTemplate::orderBy('name')->get();
        $owners = $this->assignableOwners();
        $repositories = $this->repositoriesFor($director);

        return view('hosts.create', compact('director', 'scheduleTemplates', 'owners', 'repositories'));
    }

    /** Repositories usable by hosts under this director: global ones + its own. */
    private function repositoriesFor(Director $director)
    {
        return Repository::where(fn ($q) => $q->whereNull('director_id')->orWhere('director_id', $director->id))
            ->orderBy('name')->get();
    }

    /** Validation rules shared by store() and update(). */
    private function hostRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'connection_type' => ['required', Rule::in(['agent', 'ssh', 'sftp', 'ftp', 'rsync', 'multiftp', 's3', 'ingest'])],
            'ingest_protocol' => ['nullable', Rule::in(['sftp', 'ftp', 's3'])],
            'ingest_folder' => ['nullable', 'string', 'max:1024'],
            'repository_id' => ['nullable', Rule::exists('repositories', 'id')],
            'hostname' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['nullable', 'string', 'max:45'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'remote_acct' => ['nullable', 'string', 'max:120'], // maps to username; named oddly to dodge password-manager autofill
            'auth_type' => ['nullable', Rule::in(['key', 'password', 'token'])],
            'secret' => ['nullable', 'string'],
            'private_key' => ['nullable', 'string'],
            'ftp_accounts' => ['nullable', 'array'],
            'ftp_accounts.*.label' => ['nullable', 'string', 'max:120'],
            'ftp_accounts.*.host' => ['nullable', 'string', 'max:255'],
            'ftp_accounts.*.port' => ['nullable', 'string', 'max:10'],
            'ftp_accounts.*.username' => ['nullable', 'string', 'max:190'],
            'ftp_accounts.*.password' => ['nullable', 'string', 'max:512'],
            'ftp_accounts.*.path' => ['nullable', 'string', 'max:1024'],
            'disks' => ['nullable', 'array'],
            'disks.*' => ['nullable', 'string', 'max:1024'],
            'default_schedule_template_id' => ['nullable', Rule::exists('schedule_templates', 'id')],
            'owner_id' => ['nullable', Rule::exists('users', 'id')],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Clean the multi-FTP account rows: drop rows missing a host or username,
     * default the port + label, and (on edit) keep a stored password when the
     * field is left blank for the same username@host.
     */
    private function normalizeFtpAccounts(array $rows, ?Host $existing = null): array
    {
        $prev = [];
        foreach ((array) ($existing?->ftp_accounts ?? []) as $a) {
            $prev[($a['username'] ?? '') . '@' . ($a['host'] ?? '')] = $a['password'] ?? '';
        }
        $out = [];
        foreach ($rows as $r) {
            $host = trim($r['host'] ?? '');
            $user = trim($r['username'] ?? '');
            if ($host === '' || $user === '') {
                continue;
            }
            $pass = (string) ($r['password'] ?? '');
            if ($pass === '') {
                $pass = $prev[$user . '@' . $host] ?? '';
            }
            $out[] = [
                'label' => trim($r['label'] ?? '') ?: $user,
                'host' => $host,
                'port' => trim((string) ($r['port'] ?? '')) ?: '21',
                'username' => $user,
                'password' => $pass,
                'path' => trim($r['path'] ?? ''),
            ];
        }

        return $out;
    }

    public function store(Request $request, Director $director)
    {
        $this->guardDirector($director);
        $data = $request->validate($this->hostRules());

        $data['username'] = $data['remote_acct'] ?? null;
        unset($data['remote_acct']);
        // Only admins may assign a host to another user; otherwise it inherits
        // the director's owner (user_id stays null).
        $data['user_id'] = auth()->user()->isAdmin() ? ($data['owner_id'] ?? null) : null;
        unset($data['owner_id']);
        // Drop empty disk rows.
        $data['disks'] = array_values(array_filter($data['disks'] ?? [], fn ($p) => filled($p)));
        // Multi-FTP accounts (only kept for that host type).
        $data['ftp_accounts'] = $data['connection_type'] === 'multiftp'
            ? $this->normalizeFtpAccounts($data['ftp_accounts'] ?? [])
            : null;
        if ($data['connection_type'] === 'multiftp' && empty($data['ftp_accounts'])) {
            return back()->withInput()->withErrors(['ftp_accounts' => 'Add at least one FTP account (host + username required).']);
        }

        // Ingest (receive) host: default the protocol + drop folder, auto-name a
        // username, and generate a strong password when the operator left it
        // blank (shown once on the host page to paste into cPanel/WHM).
        $targetRepoId = $data['repository_id'] ?? null;
        unset($data['repository_id']);
        if ($data['connection_type'] === 'ingest') {
            $data['ingest_protocol'] = $data['ingest_protocol'] ?? 'sftp';
            if (empty($data['username'])) {
                $data['username'] = Str::slug($data['name'], '_') ?: ('ingest_' . Str::lower(Str::random(6)));
            }
            if (empty($data['ingest_folder'])) {
                $data['ingest_folder'] = rtrim(config('backup.ingest_base', '/var/backups/ingest'), '/') . '/' . Str::slug($data['name']);
            }
            if (empty($data['secret'])) {
                $data['secret'] = Str::random(24);
            }
        } else {
            $data['ingest_protocol'] = null;
            $data['ingest_folder'] = null;
        }

        $data['status'] = $data['connection_type'] === 'agent' ? 'pending' : 'online';

        $host = $director->hosts()->create($data);

        // Auto-provision a default filesystem repository for this host so jobs
        // never hit an empty repository picker.
        $repo = \App\Models\Repository::create([
            'director_id' => $director->id,
            'name' => $host->name . ' Repository',
            'backend' => 'filesystem',
            'config' => ['path' => rtrim(config('backup.repo_base'), '/') . '/' . Str::slug($host->name)],
            'compression' => 'zstd',
            'password' => Str::random(40),
            'status' => 'active',
        ]);

        // A multi-FTP host's accounts *are* its backup definition, so create the
        // job up front — the user only needs to set/confirm a schedule. Each
        // account lands in its own folder inside this one repository.
        if ($host->connection_type === 'multiftp') {
            $tpl = $host->default_schedule_template_id
                ? \App\Models\ScheduleTemplate::find($host->default_schedule_template_id)
                : null;
            $host->jobs()->create([
                'repository_id' => $repo->id,
                'retention_policy_id' => \App\Models\RetentionPolicy::query()->value('id'),
                'name' => $host->name . ' — FTP Accounts',
                'type' => 'multiftp',
                'connector' => 'multiftp',
                'source' => [],
                'schedule_cron' => $tpl?->cron,
                'enabled' => true,
                'ad_hoc' => false,
                'prune_after_backup' => false,
            ]);

            return redirect()
                ->route('hosts.show', $host)
                ->with('status', "Host \"{$host->name}\" added with " . count($host->ftp_accounts) . " FTP account(s) and a backup job. Set its schedule below.");
        }

        // An ingest host's drop folder *is* its backup definition, so create the
        // snapshot job up front (into the chosen repository, or the default one).
        if ($host->connection_type === 'ingest') {
            $ingestRepo = $targetRepoId ? (Repository::find($targetRepoId) ?: $repo) : $repo;
            $tpl = $host->default_schedule_template_id
                ? \App\Models\ScheduleTemplate::find($host->default_schedule_template_id)
                : null;
            $host->jobs()->create([
                'repository_id' => $ingestRepo->id,
                'retention_policy_id' => \App\Models\RetentionPolicy::query()->value('id'),
                'name' => $host->name . ' — Ingest Snapshot',
                'type' => 'files',
                'connector' => 'ingest',
                'source' => ['root' => $host->ingest_folder, 'excludes' => []],
                'schedule_cron' => $tpl?->cron,
                'enabled' => true,
                'ad_hoc' => false,
                'prune_after_backup' => false,
            ]);

            return redirect()
                ->route('hosts.show', $host)
                ->with('status', "Ingest connection \"{$host->name}\" created. Point your cPanel/WHM SFTP destination at the details below; pushed files are snapshotted on the schedule.");
        }

        return redirect()
            ->route('directors.show', $director)
            ->with('status', "Host \"{$host->name}\" added with a default repository.");
    }

    public function show(Host $host)
    {
        $this->guard($host);
        // Hide one-off Quick Backup jobs from the host's job list.
        $host->load(['director:id,name', 'jobs' => fn ($q) => $q->where('ad_hoc', false)]);

        // Repositories usable for a Quick Backup: global ones + this director's.
        $repositories = Repository::where(fn ($q) => $q->whereNull('director_id')->orWhere('director_id', $host->director_id))
            ->orderBy('name')->get();
        $defaultRepoId = optional($repositories->firstWhere('name', $host->name . ' Repository'))->id
            ?? optional($repositories->first())->id;

        // Every restore point this host has produced, including one-off Quick
        // Backups (whose ad_hoc jobs are hidden from the job list above).
        $snapshots = Run::restorable()
            ->whereHas('job', fn ($q) => $q->where('host_id', $host->id))
            ->with('job:id,name,repository_id', 'job.repository:id,name')
            ->latest()
            ->limit(200)
            ->get();

        // Snapshots an operator has already asked the agent to delete, so the row
        // says so instead of offering a restore that is about to stop working.
        $pendingDeletes = \App\Models\MaintenanceTask::pendingSnapshotIds();

        return view('hosts.show', compact('host', 'repositories', 'defaultRepoId', 'snapshots', 'pendingDeletes'));
    }

    public function edit(Host $host)
    {
        $this->guard($host);
        $scheduleTemplates = \App\Models\ScheduleTemplate::orderBy('name')->get();
        $owners = $this->assignableOwners();
        $repositories = $this->repositoriesFor($host->director);

        return view('hosts.edit', compact('host', 'scheduleTemplates', 'owners', 'repositories'));
    }

    public function update(Request $request, Host $host)
    {
        $this->guard($host);
        $data = $request->validate($this->hostRules());
        $data['username'] = $data['remote_acct'] ?? null;
        unset($data['remote_acct']);
        // Only admins may reassign ownership; non-admins can't change it.
        if (auth()->user()->isAdmin()) {
            $data['user_id'] = $data['owner_id'] ?? null;
        }
        unset($data['owner_id']);
        $data['disks'] = array_values(array_filter($data['disks'] ?? [], fn ($p) => filled($p)));
        // Multi-FTP accounts (blank passwords keep their stored value); cleared
        // when the host is no longer a multi-FTP host.
        if ($data['connection_type'] === 'multiftp') {
            $data['ftp_accounts'] = $this->normalizeFtpAccounts($data['ftp_accounts'] ?? [], $host);
            if (empty($data['ftp_accounts'])) {
                return back()->withInput()->withErrors(['ftp_accounts' => 'Add at least one FTP account (host + username required).']);
            }
        } else {
            $data['ftp_accounts'] = null;
        }
        // Ingest fields: default the folder when cleared; wipe them off any host
        // that is no longer an ingest target.
        unset($data['repository_id']);
        if ($data['connection_type'] === 'ingest') {
            $data['ingest_protocol'] = $data['ingest_protocol'] ?? ($host->ingest_protocol ?: 'sftp');
            if (empty($data['ingest_folder'])) {
                $data['ingest_folder'] = $host->ingest_folder
                    ?: rtrim(config('backup.ingest_base', '/var/backups/ingest'), '/') . '/' . Str::slug($data['name']);
            }
        } else {
            $data['ingest_protocol'] = null;
            $data['ingest_folder'] = null;
        }
        // Don't overwrite stored secrets when the field is left blank on edit.
        foreach (['secret', 'private_key'] as $k) {
            if (empty($data[$k])) {
                unset($data[$k]);
            }
        }
        $host->update($data);

        return redirect()->route('hosts.show', $host)->with('status', "Host \"{$host->name}\" updated.");
    }

    /** Update a single FTP account on a multi-FTP host (inline modal edit). */
    public function updateFtpAccount(Request $request, Host $host, int $index)
    {
        $this->guard($host);
        abort_unless($host->connection_type === 'multiftp', 404);
        $accounts = $host->ftp_accounts ?? [];
        abort_unless(isset($accounts[$index]), 404);

        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:120'],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['nullable', 'string', 'max:10'],
            'username' => ['required', 'string', 'max:190'],
            'password' => ['nullable', 'string', 'max:512'],
            'path' => ['nullable', 'string', 'max:1024'],
        ]);

        $existing = $accounts[$index];
        $accounts[$index] = [
            'label' => trim($data['label'] ?? '') ?: $data['username'],
            'host' => trim($data['host']),
            'port' => trim((string) ($data['port'] ?? '')) ?: '21',
            'username' => trim($data['username']),
            // Blank password keeps the stored one.
            'password' => ($data['password'] ?? '') !== '' ? $data['password'] : ($existing['password'] ?? ''),
            'path' => trim($data['path'] ?? ''),
        ];
        $host->update(['ftp_accounts' => array_values($accounts)]);

        return back()->with('status', "FTP account \"{$accounts[$index]['label']}\" updated.");
    }

    public function destroyFtpAccount(Host $host, int $index)
    {
        $this->guard($host);
        abort_unless($host->connection_type === 'multiftp', 404);
        $accounts = $host->ftp_accounts ?? [];
        abort_unless(isset($accounts[$index]), 404);

        $label = $accounts[$index]['label'] ?? ($accounts[$index]['username'] ?? 'account');
        unset($accounts[$index]);
        // Reindex: the edit/delete routes address accounts by array position.
        $host->update(['ftp_accounts' => array_values($accounts)]);

        return back()->with('status', "FTP account \"{$label}\" removed. Backups already in the repository are untouched.");
    }

    /** Test that we can log into an agentless host (currently FTP). */
    public function testConnection(Host $host)
    {
        $this->guard($host);

        // Ingest is a passive receive target — nothing to dial out to. Point an
        // external system at the connection details and push a file to test it.
        if ($host->connection_type === 'ingest') {
            return back()->with('conn_test', 'pending:This is a receive (ingest) target — external systems push into it. Paste the connection details on this page into your cPanel/WHM SFTP destination; pushed files land in the drop folder and are snapshotted on the next scheduled run.');
        }

        // SSH-family hosts: verify the gateway can reach + log in (key auth).
        if (in_array($host->connection_type, ['ssh', 'sftp', 'rsync'], true)) {
            $addr = $host->ip_address ?: $host->hostname;
            if (! $addr) {
                return back()->with('conn_test', 'fail:No hostname or IP is set on this host.');
            }
            if ($host->auth_type === 'password' || ! $host->private_key) {
                return back()->with('conn_test', 'pending:Test Connection currently checks SSH-key auth. Password-auth hosts still back up; a key-based test is recommended.');
            }
            $port = (int) ($host->port ?: 22);
            $user = $host->username ?: 'root';
            $keyfile = tempnam(sys_get_temp_dir(), 'conn');
            file_put_contents($keyfile, rtrim((string) $host->private_key) . "\n");
            @chmod($keyfile, 0600);
            $opts = '-i ' . escapeshellarg($keyfile) . ' -p ' . $port
                . ' -o BatchMode=yes -o ConnectTimeout=10 -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null';
            exec('ssh ' . $opts . ' ' . escapeshellarg("{$user}@{$addr}") . ' echo BACKUPMGR_OK 2>&1', $out, $code);
            @unlink($keyfile);
            if ($code === 0 && in_array('BACKUPMGR_OK', $out, true)) {
                $host->forceFill(['status' => 'online', 'last_seen_at' => now()])->save();

                return back()->with('conn_test', "ok:Connected to {$addr}:{$port} as {$user} and authenticated over SSH.");
            }

            return back()->with('conn_test', "fail:Could not connect/authenticate to {$addr}:{$port} as {$user}. " . trim(implode(' ', array_slice($out, -2))));
        }

        // Multi-FTP: probe every account and report each one on its own row.
        if ($host->connection_type === 'multiftp') {
            $accounts = $host->ftp_accounts ?? [];
            if (empty($accounts)) {
                return back()->with('conn_test', 'fail:No FTP accounts are configured on this host yet.');
            }

            // Keyed by the account's position in ftp_accounts, which is also how
            // the table addresses accounts for edit and delete, so the view can
            // line a result up with the row it belongs to.
            $results = [];
            // Dead accounts each burn their full socket timeout, so a long list
            // can outrun the proxy and lose every result to a gateway timeout.
            // Stop at a budget that fits inside it and say so, rather than
            // returning nothing at all.
            $deadline = microtime(true) + 30;
            foreach ($accounts as $i => $a) {
                $label = ($a['label'] ?? '') ?: (($a['username'] ?? '') ?: 'Account ' . ($i + 1));
                // A row missing either half of the login is skipped by the agent,
                // so it silently never backs up: report it as a failure, not a pass.
                if (empty($a['host']) || empty($a['username'])) {
                    $results[$i] = ['label' => $label, 'ok' => false, 'reason' => 'No FTP host or username is set, so this account is skipped by every backup. Edit it to fill both in.'];
                    continue;
                }
                if (microtime(true) > $deadline) {
                    $results[$i] = ['label' => $label, 'ok' => false, 'reason' => 'Not tested: the run used its 30 second budget on the accounts above. Fix those, then test again.'];
                    continue;
                }
                $results[$i] = ['label' => $label] + $this->probeFtpAccount($a);
            }

            $failed = array_filter($results, fn ($r) => ! $r['ok']);
            $n = count($results);
            if (empty($failed)) {
                return back()
                    ->with('conn_test', "ok:All {$n} FTP account(s) connected, logged in and listed their directory.")
                    ->with('ftp_tests', $results);
            }

            $names = implode(', ', array_map(fn ($r) => $r['label'], $failed));

            return back()
                ->with('conn_test', 'fail:' . count($failed) . " of {$n} FTP account(s) failed: {$names}. The reason for each one is on its row below.")
                ->with('ftp_tests', $results);
        }

        if ($host->connection_type !== 'ftp') {
            return back()->with('conn_test', 'pending:Test Connection is available for FTP, Multi-FTP and SSH hosts. This connector\'s test is coming soon.');
        }
        $addr = $host->ip_address ?: $host->hostname;
        if (! $addr) {
            return back()->with('conn_test', 'fail:No hostname or IP is set on this host.');
        }
        $port = $host->port ?: 21;
        $user = $host->username ?: 'anonymous';
        $pass = $host->secret ?? '';
        $url = sprintf('ftp://%s:%s@%s:%d/', rawurlencode($user), rawurlencode($pass), $addr, $port);

        $prev = ini_set('default_socket_timeout', '12');
        $dh = @opendir($url, stream_context_create(['ftp' => ['overwrite' => true]]));
        ini_set('default_socket_timeout', $prev);

        if ($dh === false) {
            return back()->with('conn_test', "fail:Could not connect or log in to {$addr}:{$port}. Check the host, port, username, and password.");
        }
        $n = 0;
        while (readdir($dh) !== false) {
            $n++;
        }
        closedir($dh);

        return back()->with('conn_test', "ok:Connected to {$addr} and listed {$n} entries at the root. Login works.");
    }

    /**
     * Log into one FTP account and report exactly where it failed.
     *
     * The ftp:// stream wrapper collapses a refused port, a bad password and an
     * unreadable directory into one `false`, and ext-ftp is not installed on
     * every gateway, so the protocol is spoken over a plain socket instead.
     * That is the only way to give the operator a reason worth acting on.
     *
     * @param  array  $a  One row of the host's ftp_accounts JSON.
     * @return array{ok: bool, reason: string}
     */
    private function probeFtpAccount(array $a): array
    {
        $addr = trim((string) $a['host']);
        $port = (int) (($a['port'] ?? '') ?: 21);
        $user = (string) $a['username'];
        $pass = (string) ($a['password'] ?? '');
        $path = trim((string) ($a['path'] ?? ''));
        $timeout = 8;

        $conn = @stream_socket_client("tcp://{$addr}:{$port}", $errno, $errstr, $timeout);
        if (! $conn) {
            return ['ok' => false, 'reason' => $this->ftpSocketReason((int) $errno, (string) $errstr, $addr, $port)];
        }
        stream_set_timeout($conn, $timeout);

        try {
            [$code, $text] = $this->ftpRead($conn);
            if ($code !== 220) {
                return ['ok' => false, 'reason' => $code === 0
                    ? "Connected to {$addr}:{$port} but timed out waiting for the FTP greeting. Is that port really an FTP server?"
                    : "{$addr}:{$port} answered \"{$text}\" instead of an FTP greeting."];
            }

            [$code, $text] = $this->ftpSend($conn, 'USER ' . $user);
            if ($code === 331 || $code === 332) {
                [$code, $text] = $this->ftpSend($conn, 'PASS ' . $pass);
            }
            if ($code !== 230) {
                return ['ok' => false, 'reason' => $code === 0
                    ? "Timed out during login as {$user}."
                    : "Login as {$user} was rejected: \"{$text}\". Check the username and password."];
            }

            if ($path !== '' && $path !== '/') {
                [$code, $text] = $this->ftpSend($conn, 'CWD ' . $path);
                if ($code !== 250) {
                    return ['ok' => false, 'reason' => "Logged in as {$user}, but the directory \"{$path}\" could not be opened: \"{$text}\"."];
                }
            }

            $this->ftpSend($conn, 'TYPE I');

            [$code, $text] = $this->ftpSend($conn, 'PASV');
            if ($code !== 227 || ! preg_match('/(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/', $text, $m)) {
                return ['ok' => false, 'reason' => "Logged in as {$user}, but passive mode was refused: \"{$text}\". Backups need passive FTP."];
            }
            // The address the server advertises is ignored: a NAT'd server hands
            // back its private IP, which we could never reach. Only the port is
            // usable, so the data channel goes back to the control host.
            $dataPort = ((int) $m[5] << 8) + (int) $m[6];
            $data = @stream_socket_client("tcp://{$addr}:{$dataPort}", $dErrno, $dErrstr, $timeout);
            if (! $data) {
                return ['ok' => false, 'reason' => "Logged in as {$user}, but the passive data port {$dataPort} on {$addr} was unreachable. A firewall is blocking the passive port range."];
            }
            stream_set_timeout($data, $timeout);

            [$code, $text] = $this->ftpSend($conn, 'LIST');
            if ($code !== 150 && $code !== 125) {
                fclose($data);

                return ['ok' => false, 'reason' => "Logged in as {$user}, but listing " . ($path !== '' ? "\"{$path}\"" : 'the login directory') . " failed: \"{$text}\"."];
            }
            $listing = (string) stream_get_contents($data);
            fclose($data);
            $this->ftpRead($conn); // transfer-complete reply

            $entries = count(array_filter(preg_split('/\R/', trim($listing))));
            $where = $path !== '' && $path !== '/' ? "\"{$path}\"" : 'the login directory';

            return ['ok' => true, 'reason' => "Logged in as {$user} and listed {$entries} entries in {$where}."];
        } finally {
            @fwrite($conn, "QUIT\r\n");
            @fclose($conn);
        }
    }

    /**
     * Read one FTP reply, following the multi-line form ("220-" continuation
     * lines closed by the same code with a space).
     *
     * @return array{0: int, 1: string} Reply code (0 on timeout) and its text.
     */
    private function ftpRead($conn): array
    {
        $text = '';
        while (($line = fgets($conn, 2048)) !== false) {
            $text .= $line;
            if (preg_match('/^(\d{3}) /', $line, $m)) {
                return [(int) $m[1], $this->ftpTidy($text)];
            }
        }

        return [0, $this->ftpTidy($text)];
    }

    /** @return array{0: int, 1: string} */
    private function ftpSend($conn, string $command): array
    {
        @fwrite($conn, $command . "\r\n");

        return $this->ftpRead($conn);
    }

    /** Squash a reply to one short line so it fits in a UI message. */
    private function ftpTidy(string $text): string
    {
        $text = preg_replace('/\s+/', ' ', trim($text));
        $text = preg_replace('/^\d{3}[- ]/', '', (string) $text);

        return Str::limit(trim((string) $text), 160);
    }

    /** Turn a socket error into something an operator can act on. */
    private function ftpSocketReason(int $errno, string $errstr, string $addr, int $port): string
    {
        $e = strtolower($errstr);
        if (str_contains($e, 'getaddrinfo') || str_contains($e, 'name or service not known') || str_contains($e, 'no such host')) {
            return "The host name \"{$addr}\" does not resolve.";
        }
        if (str_contains($e, 'refused')) {
            return "Connection refused on {$addr}:{$port}. Nothing is listening there, or a firewall is rejecting it.";
        }
        if (str_contains($e, 'unreachable')) {
            return "{$addr}:{$port} is unreachable from the gateway.";
        }
        // A plain connect timeout comes back with errno 0 and no message.
        if ($errno === 0 || str_contains($e, 'timed out') || str_contains($e, 'timeout')) {
            return "Timed out connecting to {$addr}:{$port} after 8 seconds. The port is filtered, or the host is down.";
        }

        return "Could not reach {$addr}:{$port}: " . ($errstr !== '' ? $errstr : "error {$errno}") . '.';
    }

    /** Queue a run for every enabled (non-ad-hoc) job on this host. */
    public function backup(Host $host)
    {
        $this->guard($host);
        $jobs = $host->jobs()->where('enabled', true)->where('ad_hoc', false)->get();
        if ($jobs->isEmpty()) {
            return back()->with('status', 'This host has no enabled jobs yet. Create a backup job, or use Quick Backup for a one-time run.');
        }
        $queued = 0;
        foreach ($jobs as $job) {
            $busy = Run::where('backup_job_id', $job->id)->whereIn('status', ['queued', 'running'])->exists();
            if (! $busy) {
                Run::create(['backup_job_id' => $job->id, 'status' => 'queued']);
                $queued++;
            }
        }

        return back()->with('status', "Backup queued for {$queued} job(s) on {$host->name}. Runs on the next agent poll.");
    }

    /**
     * One-time "Quick Backup": create a hidden ad-hoc job for a single path and
     * queue it immediately. Verifies the connection + pipeline end to end without
     * committing to a saved, scheduled job. Never re-runs (no cron, hidden from
     * the jobs list, skipped by "Back Up Now").
     */
    public function quickBackup(Request $request, Host $host)
    {
        $this->guard($host);
        $data = $request->validate([
            'path' => ['required', 'string', 'max:1024'],
            'repository_id' => ['required', Rule::exists('repositories', 'id')],
        ]);

        $repo = Repository::findOrFail($data['repository_id']);
        abort_unless(is_null($repo->director_id) || $repo->director_id === $host->director_id, 403);

        $job = $host->jobs()->create([
            'repository_id' => $repo->id,
            'name' => 'Quick Backup ' . now()->format('Y-m-d H:i'),
            'type' => 'files',
            'connector' => $host->connection_type,
            'source' => ['root' => $data['path'], 'excludes' => []],
            'schedule_cron' => null,
            'enabled' => true,
            'ad_hoc' => true,
            'prune_after_backup' => false,
        ]);

        Run::create(['backup_job_id' => $job->id, 'status' => 'queued']);

        return back()->with('status', "Quick backup queued for {$host->name} ({$data['path']}). It runs on the next agent poll — its snapshot appears under Snapshots when done.");
    }

    /**
     * Generate a one-time enrollment token for an agent host (shown once).
     * If the host is already enrolled, this rotates the credential: the current
     * agent API key is revoked immediately, so a leaked key stops working and the
     * host must re-enroll with the new token.
     */
    public function enroll(Host $host)
    {
        $this->guard($host);
        if ($host->connection_type !== 'agent') {
            return back()->with('status', 'Only agent-type hosts use enrollment tokens.');
        }

        $rotating = (bool) $host->api_key;
        $plain = 'vlte_' . Str::random(40);
        $host->forceFill([
            'enrollment_token' => hash('sha256', $plain),
            'api_key' => null,   // revoke the existing agent credential
            'status' => 'pending',
        ])->save();

        \App\Models\AuditLog::record(
            $rotating ? 'key_rotate' : 'enroll',
            ($rotating ? 'Rotated agent key for host "' : 'Issued enrollment token for host "') . $host->name . '"',
            $host
        );

        return back()
            ->with('enroll_token', $plain)
            ->with('status', $rotating
                ? 'Key rotated. The old agent key is now revoked — re-run the install command below to reconnect.'
                : 'Enrollment token generated. Copy it now — it is shown only once.');
    }

    /**
     * List a directory ON THE HOST, over whatever connection the host uses, for
     * the live file browser. An empty path means "the login directory". Returns
     * directory entries only (names + is_dir) — never file contents.
     */
    public function browse(Request $request, Host $host)
    {
        $this->guard($host);
        $path = (string) $request->query('path', '');

        $result = match ($host->connection_type) {
            'agent' => $host->director->is_local
                ? $this->browseLocal($path)
                : $this->browseUnsupported('This agent host reports in on its next poll; live browsing over the agent is coming soon.'),
            'ftp' => $this->browseFtp($host, $path),
            'ssh', 'sftp', 'rsync' => $this->browseSsh($host, $path),
            default => $this->browseUnsupported('Live file browsing for ' . strtoupper($host->connection_type) . ' hosts is coming soon.'),
        };

        return response()->json($result);
    }

    /**
     * Browse a host live over SSH (key auth). Empty path = the login home
     * directory. Lists directory entries only; `ls -1Ap` marks directories
     * with a trailing slash, and `pwd` gives us the resolved absolute path.
     */
    private function browseSsh(Host $host, string $path): array
    {
        if ($host->auth_type === 'password' || ! $host->private_key) {
            return $this->browseUnsupported('Live browsing currently requires SSH-key auth on this host. Backups still run with password auth.');
        }
        $addr = $host->ip_address ?: $host->hostname;
        if (! $addr) {
            return $this->browseUnsupported('No hostname or IP is set on this host.');
        }
        $port = (int) ($host->port ?: 22);
        $user = $host->username ?: 'root';

        $keyfile = tempnam(sys_get_temp_dir(), 'brws');
        file_put_contents($keyfile, rtrim((string) $host->private_key) . "\n");
        @chmod($keyfile, 0600);
        $ssh = 'ssh -i ' . escapeshellarg($keyfile) . ' -p ' . $port
            . ' -o BatchMode=yes -o ConnectTimeout=12 -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null '
            . escapeshellarg("{$user}@{$addr}");

        $remote = 'cd ' . ($path !== '' ? escapeshellarg($path) : '"$HOME"')
            . ' >/dev/null 2>&1 && pwd && ls -1Ap 2>/dev/null';
        $out = [];
        exec($ssh . ' ' . escapeshellarg($remote) . ' 2>/dev/null', $out, $code);
        @unlink($keyfile);

        if ($code !== 0 || empty($out)) {
            return $this->browseUnsupported("Could not list that folder on {$addr} (path missing, or permission denied).");
        }

        $real = rtrim(array_shift($out), '/') ?: '/';
        $parent = $real === '/' ? null : (dirname($real) ?: '/');
        $entries = [];
        $n = 0;
        foreach ($out as $line) {
            if ($line === '') {
                continue;
            }
            $isDir = str_ends_with($line, '/');
            $name = rtrim($line, '/');
            if ($name === '' || $name === '.' || $name === '..') {
                continue;
            }
            $full = ($real === '/' ? '' : $real) . '/' . $name;
            $entries[] = ['name' => $name, 'path' => $full, 'is_dir' => $isDir];
            if (++$n >= 2000) {
                break;
            }
        }
        $this->sortEntries($entries);

        return ['path' => $real, 'parent' => $parent, 'truncated' => $n >= 2000, 'entries' => $entries];
    }

    private function browseUnsupported(string $message): array
    {
        return ['path' => '', 'parent' => null, 'entries' => [], 'error' => $message];
    }

    private function sortEntries(array &$entries): void
    {
        usort($entries, function ($a, $b) {
            if ($a['is_dir'] !== $b['is_dir']) {
                return $a['is_dir'] ? -1 : 1;
            }

            return strcasecmp($a['name'], $b['name']);
        });
    }

    /** Browse the Manager host's own filesystem (local Director, agent hosts). */
    private function browseLocal(string $path): array
    {
        $real = @realpath($path !== '' ? $path : '/');
        if ($real === false || ! is_dir($real)) {
            $real = '/';
        }
        $real = rtrim($real, '/') ?: '/';
        $parent = $real === '/' ? null : (dirname($real) ?: '/');

        $dh = @opendir($real);
        if ($dh === false) {
            return ['path' => $real, 'parent' => $parent, 'entries' => [], 'error' => 'This folder is not readable.'];
        }
        $entries = [];
        $n = 0;
        while (($name = readdir($dh)) !== false) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            $full = ($real === '/' ? '' : $real) . '/' . $name;
            $entries[] = ['name' => $name, 'path' => $full, 'is_dir' => @is_dir($full)];
            if (++$n >= 2000) {
                break;
            }
        }
        closedir($dh);
        $this->sortEntries($entries);

        return ['path' => $real, 'parent' => $parent, 'truncated' => $n >= 2000, 'entries' => $entries];
    }

    /** Browse a host live over FTP. Empty path = the account's login directory. */
    private function browseFtp(Host $host, string $path): array
    {
        $addr = $host->ip_address ?: $host->hostname;
        if (! $addr) {
            return $this->browseUnsupported('No hostname or IP is set on this host.');
        }
        $port = (int) ($host->port ?: 21);
        $user = $host->username ?: 'anonymous';
        $pass = $host->secret ?? '';

        $conn = @ftp_connect($addr, $port, 12);
        if (! $conn) {
            return $this->browseUnsupported("Could not connect to {$addr}:{$port}.");
        }
        if (! @ftp_login($conn, $user, $pass)) {
            @ftp_close($conn);

            return $this->browseUnsupported('FTP login failed. Check the username and password.');
        }
        @ftp_pasv($conn, true);

        $cwd = $path !== '' ? $path : (@ftp_pwd($conn) ?: '/');
        $cwd = '/' . ltrim($cwd, '/');
        $cwd = rtrim($cwd, '/') ?: '/';
        $parent = $cwd === '/' ? null : (dirname($cwd) ?: '/');

        $entries = [];
        $mlsd = @ftp_mlsd($conn, $cwd);
        if (is_array($mlsd)) {
            foreach ($mlsd as $e) {
                $name = $e['name'] ?? '';
                if ($name === '' || $name === '.' || $name === '..') {
                    continue;
                }
                $entries[] = [
                    'name' => $name,
                    'path' => ($cwd === '/' ? '' : $cwd) . '/' . $name,
                    'is_dir' => in_array($e['type'] ?? '', ['dir', 'cdir', 'pdir'], true),
                ];
            }
        } else {
            // Server without MLSD: fall back to NLST + SIZE (-1 means a directory).
            foreach (@ftp_nlist($conn, $cwd) ?: [] as $item) {
                $name = basename($item);
                if ($name === '' || $name === '.' || $name === '..') {
                    continue;
                }
                $abs = ($cwd === '/' ? '' : $cwd) . '/' . $name;
                $entries[] = ['name' => $name, 'path' => $abs, 'is_dir' => @ftp_size($conn, $abs) === -1];
            }
        }
        @ftp_close($conn);
        $this->sortEntries($entries);

        return ['path' => $cwd, 'parent' => $parent, 'entries' => $entries];
    }

    /** Create a folder ON THE HOST (for choosing/creating a restore target). */
    public function makeDir(Request $request, Host $host)
    {
        $this->guard($host);
        $data = $request->validate([
            'path' => ['nullable', 'string', 'max:1024'],
            'name' => ['required', 'string', 'max:255', 'regex:/^[^\/\\\\\0]+$/'],
        ]);
        $name = trim($data['name']);
        if ($name === '' || $name === '.' || $name === '..') {
            return response()->json(['error' => 'Enter a valid folder name.']);
        }
        $parent = (string) ($data['path'] ?? '');

        $result = match ($host->connection_type) {
            'agent' => $host->director->is_local
                ? $this->mkdirLocal($parent, $name)
                : ['error' => 'Creating folders over the agent is coming soon.'],
            'ftp' => $this->mkdirFtp($host, $parent, $name),
            'ssh', 'sftp', 'rsync' => $this->mkdirSsh($host, $parent, $name),
            default => ['error' => 'Creating folders on ' . strtoupper($host->connection_type) . ' hosts is coming soon.'],
        };

        return response()->json($result);
    }

    private function mkdirSsh(Host $host, string $parent, string $name): array
    {
        if ($host->auth_type === 'password' || ! $host->private_key) {
            return ['error' => 'Creating folders currently requires SSH-key auth on this host.'];
        }
        $addr = $host->ip_address ?: $host->hostname;
        if (! $addr) {
            return ['error' => 'No hostname or IP is set on this host.'];
        }
        $port = (int) ($host->port ?: 22);
        $user = $host->username ?: 'root';

        $keyfile = tempnam(sys_get_temp_dir(), 'mkd');
        file_put_contents($keyfile, rtrim((string) $host->private_key) . "\n");
        @chmod($keyfile, 0600);
        $ssh = 'ssh -i ' . escapeshellarg($keyfile) . ' -p ' . $port
            . ' -o BatchMode=yes -o ConnectTimeout=12 -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null '
            . escapeshellarg("{$user}@{$addr}");

        $target = ($parent !== '' ? escapeshellarg($parent) : '"$HOME"') . '/' . escapeshellarg($name);
        $remote = 'mkdir -p -- ' . $target . ' && cd -- ' . $target . ' && pwd';
        $out = [];
        exec($ssh . ' ' . escapeshellarg($remote) . ' 2>/dev/null', $out, $code);
        @unlink($keyfile);

        if ($code !== 0 || empty($out)) {
            return ['error' => 'Could not create the folder (permission denied?).'];
        }

        return ['ok' => true, 'path' => rtrim(end($out), '/') ?: '/'];
    }

    private function mkdirLocal(string $parent, string $name): array
    {
        $base = @realpath($parent !== '' ? $parent : '/');
        if ($base === false || ! is_dir($base)) {
            return ['error' => 'Parent folder not found.'];
        }
        $full = rtrim($base, '/') . '/' . $name;
        if (is_dir($full)) {
            return ['ok' => true, 'path' => $full];
        }
        if (! @mkdir($full, 0o755)) {
            return ['error' => 'Could not create the folder (permission denied?).'];
        }

        return ['ok' => true, 'path' => $full];
    }

    private function mkdirFtp(Host $host, string $parent, string $name): array
    {
        $addr = $host->ip_address ?: $host->hostname;
        if (! $addr) {
            return ['error' => 'No hostname or IP is set on this host.'];
        }
        $conn = @ftp_connect($addr, (int) ($host->port ?: 21), 12);
        if (! $conn || ! @ftp_login($conn, $host->username ?: 'anonymous', $host->secret ?? '')) {
            @ftp_close($conn);

            return ['error' => 'Could not connect or log in over FTP.'];
        }
        @ftp_pasv($conn, true);
        $cwd = $parent !== '' ? $parent : (@ftp_pwd($conn) ?: '/');
        $cwd = rtrim('/' . ltrim($cwd, '/'), '/') ?: '/';
        $full = ($cwd === '/' ? '' : $cwd) . '/' . $name;
        $ok = @ftp_mkdir($conn, $full) !== false;
        @ftp_close($conn);

        return $ok ? ['ok' => true, 'path' => $full] : ['error' => 'Could not create the folder (permission denied?).'];
    }

    public function destroy(Host $host)
    {
        $this->guard($host);
        $director = $host->director;
        $name = $host->name;
        $host->delete();

        return redirect()
            ->route('directors.show', $director)
            ->with('status', "Host \"{$name}\" removed.");
    }
}
