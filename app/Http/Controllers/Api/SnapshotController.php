<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Run;
use Illuminate\Http\Request;

class SnapshotController extends Controller
{
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
}
