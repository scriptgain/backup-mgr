<?php

namespace App\Http\Controllers;

use App\Models\Director;
use App\Models\StorageDevice;
use Illuminate\Http\Request;

class StorageDeviceController extends Controller
{
    /** Fleet-wide storage overview, grouped by Director. */
    public function index(Request $request)
    {
        $directors = Director::visibleTo($request->user())
            ->with('storageDevices')
            ->orderBy('name')
            ->get();

        return view('settings.storage', compact('directors'));
    }

    public function store(Request $request, Director $director)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'mount_path' => ['required', 'string', 'max:255'],
            'total_gb' => ['nullable', 'numeric', 'min:0'],
            'used_gb' => ['nullable', 'numeric', 'min:0'],
        ]);

        $director->storageDevices()->create([
            'name' => $data['name'],
            'mount_path' => rtrim($data['mount_path'], '/') ?: '/',
            'total_bytes' => isset($data['total_gb']) ? (int) ($data['total_gb'] * 1_000_000_000) : null,
            'used_bytes' => isset($data['used_gb']) ? (int) ($data['used_gb'] * 1_000_000_000) : null,
        ]);

        return back()->with('status', "Storage device \"{$data['name']}\" added.");
    }

    /** Auto-detect real disks/mounts. Works for the local Director (Manager host). */
    public function detect(Director $director)
    {
        abort_unless(auth()->user()->isAdmin() || $director->user_id === auth()->id(), 403);

        if (! $director->is_local) {
            return back()->with('status', 'Auto-detection for remote Directors reports in via the agent (coming soon). Add disks manually for now.');
        }

        $real = ['ext4', 'ext3', 'ext2', 'xfs', 'btrfs', 'zfs', 'f2fs'];
        $mounts = @file('/proc/mounts', FILE_IGNORE_NEW_LINES) ?: [];

        // Refresh the auto-detected set each run; leave manually-added disks
        // (those have no reported_at) alone.
        $director->storageDevices()->whereNotNull('reported_at')->delete();

        $seen = [];
        $count = 0;
        foreach ($mounts as $line) {
            [$dev, $mount, $type] = array_pad(explode(' ', $line), 3, '');
            $mount = str_replace('\\040', ' ', $mount);
            if (! in_array($type, $real, true)) {
                continue;
            }
            // One card per physical filesystem. A VM often mounts /, /boot,
            // /etc, /usr (bind mounts) off a single disk; every mount reports
            // the whole disk's size, so counting each inflates the total. The
            // stat device id is identical across bind mounts of one filesystem.
            $st = @stat($mount);
            $fsKey = $st ? $st['dev'] : $dev;
            if (isset($seen[$fsKey])) {
                continue;
            }
            $total = @disk_total_space($mount);
            $free = @disk_free_space($mount);
            if (! $total) {
                continue;
            }
            $seen[$fsKey] = true;
            $director->storageDevices()->create([
                'mount_path' => $mount,
                'name' => $mount === '/' ? 'Root Disk' : ucfirst(basename($mount)) . ' Disk',
                'total_bytes' => (int) $total,
                'used_bytes' => (int) ($total - $free),
                'reported_at' => now(),
            ]);
            $count++;
        }

        return back()->with('status', $count ? "Detected {$count} disk(s)." : 'No local disks found.');
    }

    public function destroy(StorageDevice $storageDevice)
    {
        $director = $storageDevice->director;
        $storageDevice->delete();

        return redirect()->route('directors.show', $director)->with('status', 'Storage device removed.');
    }
}
