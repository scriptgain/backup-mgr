<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Self-update for a self-hosted install.
 *
 * scriptgain signs the latest version + download URL + tarball sha256 into the
 * license validation response (see LicenseClient). This service compares that
 * against the local VERSION file and, when newer, downloads the release tarball,
 * verifies its checksum, backs up the current tree, extracts the new code over
 * the install (the tarball contains code + vendor but no .env/storage), runs
 * migrations, clears caches, and bumps VERSION.
 *
 * apply() is meant to run from the CLI (php artisan app:update) — the admin
 * button and the scheduler both drive it there, never inside a web request.
 */
class UpdateService
{
    /** How many pre-update safety backups to keep; older ones are pruned. */
    private const KEEP_BACKUPS = 3;

    /** Local semver, from the VERSION file at the app root. */
    public static function currentVersion(): string
    {
        $v = @file_get_contents(base_path('VERSION'));

        return $v ? trim($v) : '0.0.0';
    }

    public static function latestVersion(): ?string
    {
        return Setting::get('update_latest_version') ?: null;
    }

    /** True when the license server has advertised a newer version than ours. */
    public static function available(): bool
    {
        $latest = self::latestVersion();

        return $latest && version_compare($latest, self::currentVersion(), '>');
    }

    /** Auto-apply is opt-out (default on), per product policy. */
    public static function autoEnabled(): bool
    {
        return (Setting::get('update_auto') ?? '1') === '1';
    }

    public static function status(): array
    {
        return [
            'current' => self::currentVersion(),
            'latest' => self::latestVersion(),
            'available' => self::available(),
            'auto' => self::autoEnabled(),
            'checked_at' => Setting::get('update_checked_at'),
            'last_result' => Setting::get('update_last_result'),
        ];
    }

    /**
     * Persist the signed version fields carried on the license response so the
     * updater can act on them without a separate (unsigned) call.
     */
    public static function recordFromLicense(array $payload): void
    {
        if (! empty($payload['latest_version'])) {
            Setting::put('update_latest_version', (string) $payload['latest_version']);
        }
        if (array_key_exists('download_url', $payload)) {
            Setting::put('update_download_url', (string) ($payload['download_url'] ?? ''));
        }
        if (array_key_exists('download_sha256', $payload)) {
            Setting::put('update_download_sha256', (string) ($payload['download_sha256'] ?? ''));
        }
        Setting::put('update_checked_at', now()->toIso8601String());
    }

    /**
     * Download, verify, and apply the latest release. Returns a result array.
     * $log is an optional line sink for progress (the CLI passes the command).
     */
    public function apply(?callable $log = null): array
    {
        $log = $log ?: fn ($m) => null;

        // Single-flight lock. The scheduler fires app:update every few minutes;
        // a manual run, a slow download, or a big backup could otherwise leave
        // two applies extracting over the same tree at once (observed: a dozen
        // stacked runs, none completing). flock is released automatically when
        // the process exits, so a killed update never leaves a stuck lock.
        [$lock, $lockPath] = $this->acquireLock();
        if (! $lock) {
            $log('Another update is already running; skipping this run.');

            return $this->done(true, 'Skipped: another update is already in progress.');
        }

        try {
            if (! self::available()) {
                return $this->done(true, 'Already up to date on ' . self::currentVersion());
            }

            $latest = self::latestVersion();
            $url = Setting::get('update_download_url');
            $sha = Setting::get('update_download_sha256');
            if (! $url) {
                return $this->done(false, 'No download URL from the license server; run a license re-check first.');
            }

            $disk = Storage::disk('local');
            $tarRel = 'updates/' . $latest . '.tar.gz';

            try {
                $log("Downloading {$latest}…");
                $resp = Http::timeout(600)->get($url);
                if (! $resp->successful()) {
                    return $this->done(false, "Download failed: HTTP {$resp->status()}");
                }
                $disk->put($tarRel, $resp->body());

                if ($sha) {
                    $got = hash('sha256', $disk->get($tarRel));
                    if (! hash_equals($sha, $got)) {
                        return $this->done(false, 'Checksum mismatch; refusing to apply.');
                    }
                    $log('Checksum verified.');
                } else {
                    $log('WARNING: release has no published checksum; applying without integrity verification.');
                }

                $tarAbs = $disk->path($tarRel);
                $base = base_path();

                // Back up the current tree, excluding heavy/runtime dirs. vendor
                // is restored by re-extracting the previous release tarball, so
                // it is excluded here — including it turned a rollback snapshot
                // into a 50 MB+ archive per update and slowed every run.
                $backup = $disk->path('updates/backup-' . self::currentVersion() . '-' . now()->timestamp . '.tar.gz');
                $log('Backing up current install…');
                // Best-effort: a stray unreadable/changing file must never block
                // an update. If the safety backup can't complete, warn and keep
                // going — the previous release stays available from the vendor.
                try {
                    $this->run(['tar', 'czf', $backup, '-C', $base,
                        '--ignore-failed-read', '--warning=no-file-changed',
                        '--exclude=storage', '--exclude=node_modules', '--exclude=.git', '--exclude=vendor', '--exclude=*.bak*',
                        '.'], $log);
                    $this->pruneOldBackups($disk, $log);
                } catch (\Throwable $e) {
                    $log('WARNING: backup incomplete — ' . trim($e->getMessage()));
                }

                // Extract the new build over the install. The tarball is rooted
                // at the app root and holds no .env or storage/, so those stay.
                $log('Applying new files…');
                $this->run(['tar', 'xzf', $tarAbs, '-C', $base], $log);

                $log('Running migrations…');
                Artisan::call('migrate', ['--force' => true]);
                $log(trim(Artisan::output()));

                $log('Clearing caches…');
                Artisan::call('optimize:clear');

                file_put_contents(base_path('VERSION'), $latest . "\n");
                $log("Updated to {$latest}.");

                return $this->done(true, "Updated to {$latest}. Backup at {$backup}");
            } finally {
                // Always drop the downloaded archive, success or failure, so a
                // failed run never leaves a stale <version>.tar.gz behind.
                $disk->delete($tarRel);
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
            @unlink($lockPath);
        }
    }

    /**
     * Take an exclusive, non-blocking lock so only one update runs at a time.
     * Returns [handle, path] on success or [null, path] if another run holds it.
     */
    private function acquireLock(): array
    {
        $path = Storage::disk('local')->path('updates/.update.lock');
        @mkdir(dirname($path), 0775, true);
        $h = @fopen($path, 'c');
        if ($h === false) {
            // If we can't even open the lock file, don't wedge updates forever.
            return [null, $path];
        }
        if (! flock($h, LOCK_EX | LOCK_NB)) {
            fclose($h);

            return [null, $path];
        }

        return [$h, $path];
    }

    /** Keep only the newest self::KEEP_BACKUPS pre-update archives. */
    private function pruneOldBackups($disk, callable $log): void
    {
        $files = collect($disk->files('updates'))
            ->filter(fn ($f) => str_contains($f, 'backup-') && str_ends_with($f, '.tar.gz'))
            ->sortByDesc(fn ($f) => $disk->lastModified($f))
            ->values();

        $stale = $files->slice(self::KEEP_BACKUPS);
        foreach ($stale as $f) {
            $disk->delete($f);
        }
        if ($stale->isNotEmpty()) {
            $log('Pruned ' . $stale->count() . ' old backup(s); keeping ' . min($files->count(), self::KEEP_BACKUPS) . '.');
        }
    }

    private function run(array $cmd, callable $log): void
    {
        $p = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (! is_resource($p)) {
            throw new \RuntimeException('Failed to run: ' . implode(' ', $cmd));
        }
        $out = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        foreach ($pipes as $pipe) {
            fclose($pipe);
        }
        $code = proc_close($p);
        if ($code !== 0) {
            throw new \RuntimeException('Command failed (' . $code . '): ' . implode(' ', $cmd) . "\n" . $out);
        }
        if (trim($out) !== '') {
            $log(trim($out));
        }
    }

    private function done(bool $ok, string $message): array
    {
        Setting::put('update_last_result', ($ok ? 'ok' : 'error') . ': ' . $message . ' @ ' . now()->toIso8601String());

        return ['ok' => $ok, 'message' => $message, 'version' => self::currentVersion()];
    }
}
