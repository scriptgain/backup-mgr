<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Run;
use App\Services\SnapshotDeleter;
use Illuminate\Http\Request;

class SnapshotController extends Controller
{
    public function __construct(private SnapshotDeleter $deleter) {}

    public function index(Request $request)
    {
        return Run::whereNotNull('snapshot_id')
            ->whereHas('job.host.director', fn ($q) => $q->visibleTo(auth()->user()))
            ->when($request->integer('job_id'), fn ($q, $id) => $q->where('backup_job_id', $id))
            ->with('job:id,name,host_id')
            ->latest()
            ->paginate(50);
    }

    public function show(Run $run)
    {
        $run->load('job.host.director');
        abort_unless(
            auth()->user()->isAdmin() || $run->job?->host?->director?->user_id === auth()->id(),
            403
        );

        // file_index is cast to array on the model and is included in the payload.
        return $run->load('job:id,name');
    }

    /**
     * Delete this restore point from its repository.
     *
     * Asynchronous by nature: the agent holding the repository does the work on
     * its next check-in, so this answers 202 with what was queued.
     */
    public function destroy(Run $run)
    {
        $run->load('job.host.director', 'job.repository');
        abort_unless(
            auth()->user()->isAdmin() || $run->job?->host?->director?->user_id === auth()->id(),
            403
        );

        if (! $run->snapshot_id || $run->snapshot_expired_at) {
            return response()->json(['message' => 'This run has no snapshot left to delete.'], 422);
        }

        $result = $this->deleter->queue(collect([$run]), auth()->user());

        return response()->json([
            'queued' => $result['snapshots'] > 0,
            'snapshots' => $result['snapshots'],
            'tasks' => $result['tasks'],
            'notes' => $result['notes'],
        ], $result['snapshots'] > 0 ? 202 : 409);
    }
}
