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

        return view('hosts.create', compact('director', 'scheduleTemplates', 'owners'));
    }

    public function store(Request $request, Director $director)
    {
        $this->guardDirector($director);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'connection_type' => ['required', Rule::in(['agent', 'ssh', 'sftp', 'ftp', 'rsync', 's3'])],
            'hostname' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['nullable', 'string', 'max:45'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'remote_acct' => ['nullable', 'string', 'max:120'], // maps to username; named oddly to dodge password-manager autofill
            'auth_type' => ['nullable', Rule::in(['key', 'password', 'token'])],
            'secret' => ['nullable', 'string'],
            'private_key' => ['nullable', 'string'],
            'disks' => ['nullable', 'array'],
            'disks.*' => ['nullable', 'string', 'max:1024'],
            'default_schedule_template_id' => ['nullable', Rule::exists('schedule_templates', 'id')],
            'owner_id' => ['nullable', Rule::exists('users', 'id')],
            'notes' => ['nullable', 'string'],
        ]);

        $data['username'] = $data['remote_acct'] ?? null;
        unset($data['remote_acct']);
        // Only admins may assign a host to another user; otherwise it inherits
        // the director's owner (user_id stays null).
        $data['user_id'] = auth()->user()->isAdmin() ? ($data['owner_id'] ?? null) : null;
        unset($data['owner_id']);
        // Drop empty disk rows.
        $data['disks'] = array_values(array_filter($data['disks'] ?? [], fn ($p) => filled($p)));
        $data['status'] = $data['connection_type'] === 'agent' ? 'pending' : 'online';

        $host = $director->hosts()->create($data);

        // Auto-provision a default filesystem repository for this host so jobs
        // never hit an empty repository picker.
        \App\Models\Repository::create([
            'director_id' => $director->id,
            'name' => $host->name . ' Repository',
            'backend' => 'filesystem',
            'config' => ['path' => rtrim(config('backup.repo_base'), '/') . '/' . Str::slug($host->name)],
            'compression' => 'zstd',
            'password' => Str::random(40),
            'status' => 'active',
        ]);

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

        return view('hosts.show', compact('host', 'repositories', 'defaultRepoId'));
    }

    public function edit(Host $host)
    {
        $this->guard($host);
        $scheduleTemplates = \App\Models\ScheduleTemplate::orderBy('name')->get();
        $owners = $this->assignableOwners();

        return view('hosts.edit', compact('host', 'scheduleTemplates', 'owners'));
    }

    public function update(Request $request, Host $host)
    {
        $this->guard($host);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'connection_type' => ['required', Rule::in(['agent', 'ssh', 'sftp', 'ftp', 'rsync', 's3'])],
            'hostname' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['nullable', 'string', 'max:45'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'remote_acct' => ['nullable', 'string', 'max:120'], // maps to username
            'auth_type' => ['nullable', Rule::in(['key', 'password', 'token'])],
            'secret' => ['nullable', 'string'],
            'private_key' => ['nullable', 'string'],
            'disks' => ['nullable', 'array'],
            'disks.*' => ['nullable', 'string', 'max:1024'],
            'default_schedule_template_id' => ['nullable', Rule::exists('schedule_templates', 'id')],
            'owner_id' => ['nullable', Rule::exists('users', 'id')],
            'notes' => ['nullable', 'string'],
        ]);
        $data['username'] = $data['remote_acct'] ?? null;
        unset($data['remote_acct']);
        // Only admins may reassign ownership; non-admins can't change it.
        if (auth()->user()->isAdmin()) {
            $data['user_id'] = $data['owner_id'] ?? null;
        }
        unset($data['owner_id']);
        $data['disks'] = array_values(array_filter($data['disks'] ?? [], fn ($p) => filled($p)));
        // Don't overwrite stored secrets when the field is left blank on edit.
        foreach (['secret', 'private_key'] as $k) {
            if (empty($data[$k])) {
                unset($data[$k]);
            }
        }
        $host->update($data);

        return redirect()->route('hosts.show', $host)->with('status', "Host \"{$host->name}\" updated.");
    }

    /** Test that we can log into an agentless host (currently FTP). */
    public function testConnection(Host $host)
    {
        $this->guard($host);
        if ($host->connection_type !== 'ftp') {
            return back()->with('conn_test', 'pending:Test Connection is available for FTP hosts right now; SSH/SFTP coming soon.');
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
            default => $this->browseUnsupported('Live file browsing for ' . strtoupper($host->connection_type) . ' hosts is coming soon.'),
        };

        return response()->json($result);
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
            default => ['error' => 'Creating folders on ' . strtoupper($host->connection_type) . ' hosts is coming soon.'],
        };

        return response()->json($result);
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
