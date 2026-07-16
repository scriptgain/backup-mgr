<?php

namespace App\Http\Controllers;

use App\Models\Run;

class SnapshotController extends Controller
{
    public function index()
    {
        $runs = Run::whereNotNull('snapshot_id')
            ->whereHas('job.host.director', fn ($q) => $q->visibleTo(auth()->user()))
            ->with('job.host')
            ->latest()
            ->limit(200)
            ->get();

        return view('snapshots.index', compact('runs'));
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
}
