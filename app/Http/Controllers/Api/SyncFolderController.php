<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Host;
use App\Models\SyncFolder;
use Illuminate\Http\Request;

class SyncFolderController extends Controller
{
    public function index(Request $request)
    {
        return SyncFolder::query()
            ->when($request->integer('director_id'), fn ($q, $id) => $q->where('director_id', $id))
            ->with('sourceHost:id,name', 'director:id,name')
            ->latest()
            ->paginate(50);
    }

    public function store(Request $request)
    {
        $data = $this->validateSyncFolder($request);

        // Director is inherited from the source host, matching the web flow.
        $data['director_id'] = Host::findOrFail($data['source_host_id'])->director_id;
        $data['user_id'] = $request->user()->id;
        $data['status'] = 'idle';

        return response()->json(SyncFolder::create($data), 201);
    }

    public function show(SyncFolder $syncFolder)
    {
        return $syncFolder->load('sourceHost:id,name', 'director:id,name', 'targetHosts:id,name');
    }

    public function update(Request $request, SyncFolder $syncFolder)
    {
        $data = $this->validateSyncFolder($request, updating: true);

        if (isset($data['source_host_id'])) {
            $data['director_id'] = Host::findOrFail($data['source_host_id'])->director_id;
        }

        $syncFolder->update($data);

        return $syncFolder;
    }

    public function destroy(SyncFolder $syncFolder)
    {
        $syncFolder->delete();

        return response()->noContent();
    }

    private function validateSyncFolder(Request $request, bool $updating = false): array
    {
        $req = $updating ? 'sometimes' : 'required';

        $data = $request->validate([
            'name' => [$req, 'string', 'max:120'],
            'source_host_id' => [$req, 'exists:hosts,id'],
            'source_path' => [$req, 'string', 'max:1024', 'starts_with:/'],
            'targets' => [$req, 'array', 'min:1'],
            'targets.*.host_id' => ['required', 'exists:hosts,id', 'different:source_host_id'],
            'targets.*.path' => ['required', 'string', 'max:1024', 'starts_with:/'],
            'interval_minutes' => [$req, 'integer', 'min:1', 'max:10080'],
            'delete_extra' => ['sometimes', 'boolean'],
            'enabled' => ['sometimes', 'boolean'],
        ]);

        // Normalise targets to plain [{host_id, path}] rows, as the model + agent expect.
        if (isset($data['targets'])) {
            $data['targets'] = array_values(array_map(fn ($t) => [
                'host_id' => (int) $t['host_id'],
                'path' => $t['path'],
            ], $data['targets']));
        }

        return $data;
    }
}
