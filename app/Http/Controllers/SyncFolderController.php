<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Host;
use App\Models\SyncFolder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SyncFolderController extends Controller
{
    public function index(Request $request)
    {
        $folders = SyncFolder::visibleTo($request->user())
            ->with('sourceHost.director')
            ->latest()
            ->get();

        return view('settings.sync.index', compact('folders'));
    }

    public function create(Request $request)
    {
        return view('settings.sync.form', [
            'folder' => new SyncFolder(['interval_minutes' => 15, 'enabled' => true]),
            'hosts' => $this->hostsFor($request),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $source = Host::findOrFail($data['source_host_id']);
        $this->guardHost($request, $source);

        $folder = SyncFolder::create($data + [
            'user_id' => $request->user()->id,
            'director_id' => $source->director_id,
            'status' => 'idle',
        ]);

        AuditLog::record('created', 'Sync folder "'.$folder->name.'" created', $folder);

        return redirect()->route('settings.sync.index')->with('status', 'Sync folder created.');
    }

    public function edit(Request $request, SyncFolder $syncFolder)
    {
        $this->guard($request, $syncFolder);

        return view('settings.sync.form', [
            'folder' => $syncFolder,
            'hosts' => $this->hostsFor($request),
        ]);
    }

    public function update(Request $request, SyncFolder $syncFolder)
    {
        $this->guard($request, $syncFolder);
        $data = $this->validated($request);
        $source = Host::findOrFail($data['source_host_id']);
        $this->guardHost($request, $source);

        $syncFolder->update($data + ['director_id' => $source->director_id]);

        AuditLog::record('updated', 'Sync folder "'.$syncFolder->name.'" updated', $syncFolder);

        return redirect()->route('settings.sync.index')->with('status', 'Sync folder updated.');
    }

    public function destroy(Request $request, SyncFolder $syncFolder)
    {
        $this->guard($request, $syncFolder);
        $syncFolder->delete();

        AuditLog::record('deleted', 'Sync folder "'.$syncFolder->name.'" deleted', $syncFolder);

        return redirect()->route('settings.sync.index')->with('status', 'Sync folder deleted.');
    }

    public function toggle(Request $request, SyncFolder $syncFolder)
    {
        $this->guard($request, $syncFolder);
        $syncFolder->update(['enabled' => ! $syncFolder->enabled]);

        return back()->with('status', $syncFolder->enabled ? 'Sync enabled.' : 'Sync paused.');
    }

    public function runNow(Request $request, SyncFolder $syncFolder)
    {
        $this->guard($request, $syncFolder);
        // Force it due; the gateway picks it up on its next poll.
        $syncFolder->update(['last_synced_at' => null, 'status' => 'idle']);

        return back()->with('status', 'Sync queued — it runs on the next agent poll.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'source_host_id' => ['required', 'exists:hosts,id'],
            'source_path' => ['required', 'string', 'max:1024', 'starts_with:/'],
            'targets' => ['required', 'array', 'min:1'],
            'targets.*.host_id' => ['required', 'exists:hosts,id', 'different:source_host_id'],
            'targets.*.path' => ['required', 'string', 'max:1024', 'starts_with:/'],
            'interval_minutes' => ['required', 'integer', 'min:1', 'max:10080'],
            'delete_extra' => ['sometimes', 'boolean'],
            'enabled' => ['sometimes', 'boolean'],
        ]);

        $data['delete_extra'] = $request->boolean('delete_extra');
        $data['enabled'] = $request->boolean('enabled');
        // Normalise targets to plain [{host_id, path}] arrays.
        $data['targets'] = array_values(array_map(fn ($t) => [
            'host_id' => (int) $t['host_id'],
            'path' => $t['path'],
        ], $data['targets']));

        return $data;
    }

    private function hostsFor(Request $request)
    {
        return Host::whereHas('director', fn ($q) => $q->visibleTo($request->user()))
            ->with('director')
            ->orderBy('name')
            ->get();
    }

    private function guard(Request $request, SyncFolder $folder): void
    {
        abort_unless($request->user()->isAdmin() || $folder->user_id === $request->user()->id, 403);
    }

    private function guardHost(Request $request, Host $host): void
    {
        abort_unless(
            $request->user()->isAdmin() || $host->director?->user_id === $request->user()->id,
            403
        );
    }
}
