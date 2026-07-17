<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Restore;
use App\Models\Run;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RestoreController extends Controller
{
    private function guard(Restore $restore): void
    {
        abort_unless(
            auth()->user()->isAdmin() || $restore->host?->director?->user_id === auth()->id(),
            403
        );
    }

    public function index(Request $request)
    {
        return Restore::whereHas('host.director', fn ($q) => $q->visibleTo(auth()->user()))
            ->when($request->integer('host_id'), fn ($q, $id) => $q->where('host_id', $id))
            ->with('host:id,name', 'run:id')
            ->latest()
            ->paginate(50);
    }

    public function show(Restore $restore)
    {
        $restore->load('host.director');
        $this->guard($restore);

        return $restore->load('host:id,name', 'run');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'run_id' => ['required', Rule::exists('runs', 'id')],
            'host_id' => ['nullable', Rule::exists('hosts', 'id')],
            'target_path' => ['required', 'string', 'max:1024', 'starts_with:/'],
            'paths' => ['nullable', 'array'],
            'paths.*' => ['string', 'max:2048'],
            'overwrite' => ['nullable', Rule::in(['overwrite', 'skip', 'keep_newer'])],
            'restore_ownership' => ['boolean'],
            'restore_permissions' => ['boolean'],
            'strip_paths' => ['boolean'],
            'dry_run' => ['boolean'],
        ]);

        // Snapshot is derived from the run (snapshots are not a standalone table).
        $run = Run::with('job.host.director')->findOrFail($data['run_id']);
        abort_unless(
            auth()->user()->isAdmin() || $run->job?->host?->director?->user_id === auth()->id(),
            403
        );
        // A redirect target host must also be visible to the caller.
        if (! empty($data['host_id'])) {
            $target = \App\Models\Host::findOrFail($data['host_id']);
            abort_unless($target->isVisibleTo(auth()->user()), 403);
        }

        $restore = Restore::create([
            'run_id' => $run->id,
            // Restore to the original host unless another target is given.
            'host_id' => $data['host_id'] ?? $run->job?->host_id,
            'snapshot_id' => $run->snapshot_id,
            'target_path' => $data['target_path'],
            'paths' => array_values(array_filter($data['paths'] ?? [], fn ($p) => filled($p))) ?: null,
            'overwrite' => $data['overwrite'] ?? 'overwrite',
            'restore_ownership' => $request->boolean('restore_ownership'),
            'restore_permissions' => $request->boolean('restore_permissions'),
            'strip_paths' => $request->boolean('strip_paths'),
            'dry_run' => $request->boolean('dry_run'),
            'status' => 'queued',
        ]);

        // An agent picks it up on its next restore poll.
        return response()->json($restore, 202);
    }

    public function destroy(Restore $restore)
    {
        $restore->load('host.director');
        $this->guard($restore);
        $restore->delete();

        return response()->noContent();
    }
}
