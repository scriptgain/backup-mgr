<?php

namespace App\Http\Controllers;

use App\Models\Setting;
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
}
