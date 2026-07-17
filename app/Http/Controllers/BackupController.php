<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\RemoteDatabaseBackup;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Panel backup + restore. The reliable core is a configuration snapshot of the
 * DB-driven Setting store (JSON), which restores on any install. A full
 * mysqldump is offered as a complete restore point download.
 */
class BackupController extends Controller
{
    public function index()
    {
        return view('settings.backup');
    }

    /** Download the panel configuration (the Setting store) as JSON. */
    public function downloadConfig()
    {
        $snapshot = [
            'product' => config('brand.name'),
            'app' => config('app.name'),
            'version' => trim((string) @file_get_contents(base_path('VERSION'))),
            'exported_at' => now()->toIso8601String(),
            'settings' => Setting::all()->pluck('value', 'key')->all(),
        ];
        Setting::put('last_config_backup_at', now()->toIso8601String());

        $name = Str::slug(config('app.name') ?: 'panel') . '-config-' . now()->format('Ymd-His') . '.json';

        return response()->streamDownload(function () use ($snapshot) {
            echo json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }, $name, ['Content-Type' => 'application/json']);
    }

    /** Download a gzipped mysqldump of the whole database (complete restore point). */
    public function downloadDatabase()
    {
        $db = config('database.connections.' . config('database.default'));
        if (($db['driver'] ?? '') !== 'mysql' || ! function_exists('proc_open')) {
            return back()->with('status', 'Full database backup needs MySQL and shell access on this host.');
        }

        $name = Str::slug(config('app.name') ?: 'panel') . '-db-' . now()->format('Ymd-His') . '.sql.gz';

        return response()->streamDownload(function () use ($db) {
            $cmd = ['mysqldump', '--single-transaction', '--quick', '--no-tablespaces',
                '-h', (string) $db['host'], '-P', (string) ($db['port'] ?? 3306),
                '-u', (string) $db['username'], (string) $db['database']];
            // Pass the password via the environment so it never appears in the process list.
            $env = array_merge(getenv() ?: [], ['MYSQL_PWD' => (string) $db['password']]);
            $gz = deflate_init(ZLIB_ENCODING_GZIP);
            $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $env);
            if (is_resource($proc)) {
                while (! feof($pipes[1])) {
                    $chunk = fread($pipes[1], 1 << 16);
                    if ($chunk !== false && $chunk !== '') {
                        echo deflate_add($gz, $chunk, ZLIB_NO_FLUSH);
                    }
                }
                echo deflate_add($gz, '', ZLIB_FINISH);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($proc);
            }
        }, $name, ['Content-Type' => 'application/gzip']);
    }

    /** Restore configuration from an uploaded config JSON (overwrites settings). */
    public function restore(Request $request)
    {
        $request->validate([
            'backup' => ['required', 'file', 'max:5120'],
        ]);

        $data = json_decode((string) file_get_contents($request->file('backup')->getRealPath()), true);
        if (! is_array($data) || ! isset($data['settings']) || ! is_array($data['settings'])) {
            return back()->with('status', 'That file is not a valid configuration backup.');
        }

        $n = 0;
        foreach ($data['settings'] as $key => $value) {
            Setting::put((string) $key, $value === null ? null : (string) $value);
            $n++;
        }

        return back()->with('status', "Restored {$n} configuration setting(s).");
    }

    /** Save the automated remote database-backup schedule + destination. */
    public function saveSchedule(Request $request)
    {
        $data = $request->validate([
            'dbbackup_frequency' => ['nullable', 'in:daily,weekly'],
            'dbbackup_time' => ['nullable', 'date_format:H:i'],
            'dbbackup_retention' => ['nullable', 'integer', 'min:1', 'max:365'],
            'dbbackup_transport' => ['nullable', 'in:local,ftp,sftp,rsync,dropbox'],
            'dbbackup_local_path' => ['nullable', 'string', 'max:500'],
            'dbbackup_ftp_host' => ['nullable', 'string', 'max:191'], 'dbbackup_ftp_port' => ['nullable', 'integer'],
            'dbbackup_ftp_user' => ['nullable', 'string', 'max:191'], 'dbbackup_ftp_pass' => ['nullable', 'string', 'max:191'],
            'dbbackup_ftp_path' => ['nullable', 'string', 'max:500'],
            'dbbackup_sftp_host' => ['nullable', 'string', 'max:191'], 'dbbackup_sftp_port' => ['nullable', 'integer'],
            'dbbackup_sftp_user' => ['nullable', 'string', 'max:191'], 'dbbackup_sftp_key' => ['nullable', 'string', 'max:8000'],
            'dbbackup_sftp_path' => ['nullable', 'string', 'max:500'],
            'dbbackup_rsync_host' => ['nullable', 'string', 'max:191'], 'dbbackup_rsync_port' => ['nullable', 'integer'],
            'dbbackup_rsync_user' => ['nullable', 'string', 'max:191'], 'dbbackup_rsync_key' => ['nullable', 'string', 'max:8000'],
            'dbbackup_rsync_path' => ['nullable', 'string', 'max:500'],
            'dbbackup_dropbox_token' => ['nullable', 'string', 'max:500'], 'dbbackup_dropbox_path' => ['nullable', 'string', 'max:500'],
        ]);

        Setting::put('dbbackup_enabled', $request->boolean('dbbackup_enabled') ? '1' : '0');
        Setting::put('dbbackup_ftp_passive', $request->boolean('dbbackup_ftp_passive') ? '1' : '0');

        // Secrets: keep the stored value when left blank.
        $secret = ['dbbackup_ftp_pass', 'dbbackup_sftp_key', 'dbbackup_rsync_key', 'dbbackup_dropbox_token'];
        foreach ($data as $k => $v) {
            if (in_array($k, $secret, true)) {
                if (! empty($v)) {
                    Setting::put($k, (string) $v);
                }
            } else {
                Setting::put($k, $v === null ? '' : (string) $v);
            }
        }

        return redirect()->route('settings.backup.index')->with('status', 'Automated backup settings saved.');
    }

    /** Queue an immediate backup (serviced by the scheduler within a minute). */
    public function runNow()
    {
        Setting::put('dbbackup_requested', '1');
        Setting::put('dbbackup_last_result', 'queued: requested ' . now()->toIso8601String());

        return back()->with('status', 'Backup queued. It will run within a minute; refresh for the result.');
    }
}
