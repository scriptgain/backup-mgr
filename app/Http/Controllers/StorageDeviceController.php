<?php

namespace App\Http\Controllers;

use App\Models\Director;
use App\Models\StorageDevice;
use App\Services\DiskInventory;
use Illuminate\Http\Request;

class StorageDeviceController extends Controller
{
    /** Fleet-wide storage overview, grouped by Director. */
    public function index(Request $request, DiskInventory $disks)
    {
        // Re-read the local node's disks if the last reading has gone stale, so
        // opening this page never shows usage from before the last backup.
        // Remote Directors still report in through their own agent.
        foreach (Director::visibleTo($request->user())->where('is_local', true)->get() as $local) {
            $disks->refreshIfStale($local);
        }

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

    /**
     * Auto-detect real disks/mounts. Works for the local Director (Manager host).
     *
     * The same sync runs automatically after every backup and maintenance task
     * (see DiskInventory), so this button is now a "refresh right now" rather
     * than the only way to get current numbers.
     */
    public function detect(Director $director, DiskInventory $disks)
    {
        abort_unless(auth()->user()->isAdmin() || $director->user_id === auth()->id(), 403);

        if (! $director->is_local) {
            return back()->with('status', 'Auto-detection for remote Directors reports in via the agent (coming soon). Add disks manually for now.');
        }

        $count = $disks->refresh($director);

        return back()->with('status', $count ? "Detected {$count} disk(s)." : 'No local disks found.');
    }

    public function destroy(StorageDevice $storageDevice)
    {
        $director = $storageDevice->director;
        $storageDevice->delete();

        return redirect()->route('directors.show', $director)->with('status', 'Storage device removed.');
    }
}
