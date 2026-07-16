<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $query = AuditLog::with('user:id,name,email')->latest();

        if ($action = $request->query('action')) {
            $query->where('action', $action);
        }

        $logs = $query->paginate(50)->withQueryString();
        $actions = AuditLog::query()->distinct()->orderBy('action')->pluck('action');

        return view('settings.audit.index', compact('logs', 'actions', 'action'));
    }

    /** Delete the selected audit entries. */
    public function destroySelected(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $n = AuditLog::whereIn('id', $data['ids'])->delete();

        return back()->with('status', "Deleted {$n} audit ".str('entry')->plural($n).'.');
    }

    /** Clear the entire audit log. */
    public function destroyAll(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $n = AuditLog::query()->delete();

        return redirect()->route('settings.audit.index')->with('status', "Cleared {$n} audit ".str('entry')->plural($n).'.');
    }
}
