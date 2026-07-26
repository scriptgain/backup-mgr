<?php

namespace App\Services;

use App\Models\Director;

/**
 * Reads the real filesystems on this node and syncs them into a Director's
 * storage devices.
 *
 * This used to be inline in StorageDeviceController::detect, reachable only by
 * clicking "Detect Disks", which meant the numbers on the Storage page were as
 * stale as the last click: a backup could add 6 GB and the page would still
 * show yesterday's usage. It lives here so the same refresh can also run right
 * after a backup or maintenance task finishes, and lazily when the page is
 * viewed.
 */
class DiskInventory
{
    /** Filesystem types that represent real storage, not kernel/virtual mounts. */
    private const REAL_FS = ['ext4', 'ext3', 'ext2', 'xfs', 'btrfs', 'zfs', 'f2fs'];

    /**
     * Sync auto-detected devices for a local Director and return how many were
     * seen. Manually-added devices (reported_at null) are never touched.
     *
     * Rows are matched on mount_path and updated in place rather than deleted
     * and recreated: this now runs after every backup, and churning primary keys
     * that often would break any reference to a device id.
     */
    public function refresh(Director $director): int
    {
        if (! $director->is_local) {
            return 0;
        }

        $seenFs = [];
        $seenPaths = [];
        foreach (@file('/proc/mounts', FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            [$dev, $mount, $type] = array_pad(explode(' ', $line), 3, '');
            $mount = str_replace('\\040', ' ', $mount);
            if (! in_array($type, self::REAL_FS, true)) {
                continue;
            }
            // One card per physical filesystem. A VM often mounts /, /boot,
            // /etc, /usr (bind mounts) off a single disk; every mount reports
            // the whole disk's size, so counting each inflates the total. The
            // stat device id is identical across bind mounts of one filesystem.
            $st = @stat($mount);
            $fsKey = $st ? $st['dev'] : $dev;
            if (isset($seenFs[$fsKey])) {
                continue;
            }
            $total = @disk_total_space($mount);
            $free = @disk_free_space($mount);
            if (! $total) {
                continue;
            }
            $seenFs[$fsKey] = true;
            $seenPaths[] = $mount;

            $director->storageDevices()->updateOrCreate(
                ['mount_path' => $mount],
                [
                    'name' => $mount === '/' ? 'Root Disk' : ucfirst(basename($mount)) . ' Disk',
                    'total_bytes' => (int) $total,
                    'used_bytes' => (int) ($total - $free),
                    'reported_at' => now(),
                ]
            );
        }

        // Drop auto-detected devices whose filesystem is gone (unmounted disk).
        $director->storageDevices()
            ->whereNotNull('reported_at')
            ->whereNotIn('mount_path', $seenPaths ?: ['\0'])
            ->delete();

        return count($seenPaths);
    }

    /**
     * Refresh only if the newest reading is older than $seconds. Used on page
     * load and after agent reports so a busy master isn't re-statting every
     * mount on every request.
     */
    public function refreshIfStale(Director $director, int $seconds = 120): int
    {
        if (! $director->is_local) {
            return 0;
        }
        $newest = $director->storageDevices()->whereNotNull('reported_at')->max('reported_at');
        if ($newest && \Illuminate\Support\Carbon::parse($newest)->diffInSeconds(now(), true) < $seconds) {
            return 0;
        }

        return $this->refresh($director);
    }
}
