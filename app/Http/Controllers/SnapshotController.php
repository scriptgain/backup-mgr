<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceTask;
use App\Models\Run;
use App\Services\SnapshotDeleter;
use Illuminate\Http\Request;

class SnapshotController extends Controller
{
    public function __construct(private SnapshotDeleter $deleter) {}

    public function index()
    {
        $runs = Run::restorable()
            ->whereHas('job.host.director', fn ($q) => $q->visibleTo(auth()->user()))
            ->with('job.host', 'job.repository')
            ->latest()
            ->limit(200)
            ->get();

        return view('snapshots.index', [
            'runs' => $runs,
            'pendingDeletes' => MaintenanceTask::pendingSnapshotIds(),
        ]);
    }

    public function browse(Run $run)
    {
        $run->load('job.host.director');
        abort_unless(
            auth()->user()->isAdmin() || $run->job?->host?->director?->user_id === auth()->id(),
            403
        );

        return view('snapshots.browse', compact('run'));
    }

    /**
     * Delete one restore point from its repository.
     *
     * This deletes backup data, unlike deleting a run, which only drops the
     * record of it. The work is handed to the agent (see SnapshotDeleter).
     */
    public function destroy(Run $run)
    {
        $run->load('job.host.director', 'job.repository');
        $this->guard($run);

        return $this->deleter->flash(
            back(),
            $this->deleter->queue(collect([$run]), auth()->user())
        );
    }

    /** Delete every selected restore point, grouped one task per repository. */
    public function destroyMany(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $runs = Run::restorable()
            ->whereIn('id', $data['ids'])
            ->with('job.host.director', 'job.repository')
            ->get()
            ->filter(fn (Run $run) => $this->visible($run));

        if ($runs->isEmpty()) {
            return back()->with('warning', 'No matching restore points were selected.');
        }

        return $this->deleter->flash(back(), $this->deleter->queue($runs, auth()->user()));
    }

    private function visible(Run $run): bool
    {
        return $run->job?->host?->isVisibleTo(auth()->user()) ?? false;
    }

    private function guard(Run $run): void
    {
        abort_unless($this->visible($run), 403);
    }
}
