<?php

namespace App\Http\Controllers;

use App\Models\Run;

class RunController extends Controller
{
    public function show(Run $run)
    {
        $run->load('job.host.director', 'job.repository');
        $this->guard($run);

        return view('runs.show', compact('run'));
    }

    private function guard(Run $run): void
    {
        abort_unless(
            auth()->user()->isAdmin() || $run->job?->host?->director?->user_id === auth()->id(),
            403
        );
    }

    public function destroy(Run $run)
    {
        $run->loadMissing('job.host.director');
        $this->guard($run);
        $job = $run->job;
        $run->delete();

        return redirect()
            ->route('jobs.show', $job)
            ->with('status', 'Run deleted.');
    }
}
