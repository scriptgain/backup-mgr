<?php

namespace App\Http\Controllers;

use App\Models\Host;
use App\Models\Restore;
use App\Models\Run;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RestoreController extends Controller
{
    public function index()
    {
        $restores = Restore::whereHas('host.director', fn ($q) => $q->visibleTo(auth()->user()))
            ->with('host:id,name', 'run.job:id,name')->latest()->limit(200)->get();

        return view('restores.index', compact('restores'));
    }

    /** Delete the selected restore records (visibility-scoped). */
    public function bulkDestroy(Request $request)
    {
        $ids = array_filter((array) $request->input('ids', []));
        if ($ids) {
            Restore::whereIn('id', $ids)
                ->whereHas('host.director', fn ($q) => $q->visibleTo(auth()->user()))
                ->delete();
        }

        return back()->with('status', 'Selected restores deleted.');
    }

    /** Advanced restore page: full Bareos-style option set for one snapshot. */
    public function create(Run $run)
    {
        $run->load('job.host.director');
        abort_unless(auth()->user()->isAdmin() || $run->job?->host?->director?->user_id === auth()->id(), 403);
        abort_if(! $run->snapshot_id, 404);

        $origin = ($run->job?->source['root'] ?? '') ?: '/var/restore';
        // Hosts that can receive a redirect restore (same visibility scope).
        $hosts = Host::whereHas('director', fn ($q) => $q->visibleTo(auth()->user()))
            ->orderBy('name')->get(['id', 'name']);
        $hasIndex = is_array($run->file_index) && count($run->file_index);

        return view('restores.create', compact('run', 'origin', 'hosts', 'hasIndex'));
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

        $run = Run::with('job.host.director')->findOrFail($data['run_id']);
        abort_unless(auth()->user()->isAdmin() || $run->job?->host?->director?->user_id === auth()->id(), 403);
        if (! $run->snapshot_id) {
            return back()->with('status', 'That run has no snapshot to restore.');
        }

        // Redirect-restore target: default to the original host; if another host
        // is chosen it must be in the same Director (same visibility scope).
        $targetHostId = $run->job?->host_id;
        if (! empty($data['host_id'])) {
            $target = Host::with('director')->findOrFail($data['host_id']);
            abort_unless(
                auth()->user()->isAdmin() || $target->director?->user_id === auth()->id(),
                403
            );
            $targetHostId = $target->id;
        }

        $attrs = [
            'run_id' => $run->id,
            'host_id' => $targetHostId,
            'snapshot_id' => $run->snapshot_id,
            'target_path' => $data['target_path'],
            'paths' => array_values(array_filter($data['paths'] ?? [], fn ($p) => filled($p))) ?: null,
            'status' => 'queued',
        ];
        // Advanced options are only present on the advanced form (marked with a
        // hidden flag); the quick modal falls back to the column defaults.
        if ($request->boolean('advanced')) {
            $attrs['overwrite'] = $data['overwrite'] ?? 'overwrite';
            $attrs['restore_ownership'] = $request->boolean('restore_ownership');
            $attrs['restore_permissions'] = $request->boolean('restore_permissions');
            $attrs['strip_paths'] = $request->boolean('strip_paths');
            $attrs['dry_run'] = $request->boolean('dry_run');
        }

        $restore = Restore::create($attrs);
        \App\Models\AuditLog::record('restore', 'Restore queued to "'.$data['target_path'].'" on '.(optional(Host::find($targetHostId))->name ?? 'host'), $restore);

        return redirect()->route('restores.index')
            ->with('status', ($request->boolean('dry_run') ? 'Dry-run restore queued (verify only). ' : 'Restore queued. ').'It runs on the next agent poll.');
    }
}
