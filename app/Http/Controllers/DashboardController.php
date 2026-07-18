<?php

namespace App\Http\Controllers;

use App\Models\BackupJob;
use App\Models\Director;
use App\Models\Host;
use App\Models\Run;
use App\Models\StorageDevice;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();
        $visible = fn ($q) => $q->visibleTo($user);

        $stats = [
            'directors' => Director::visibleTo($user)->count(),
            'hosts' => Host::whereHas('director', $visible)->count(),
            'jobs' => BackupJob::where('enabled', true)->whereHas('host.director', $visible)->count(),
            'restore_points' => Run::where('status', 'success')->whereHas('job.host.director', $visible)->count(),
        ];

        // Fleet health.
        $failed24h = Run::where('status', 'failed')->where('created_at', '>=', now()->subDay())
            ->whereHas('job.host.director', $visible)->count();

        $staleHosts = Host::where('connection_type', 'agent')
            ->whereHas('director', $visible)
            ->whereNotNull('api_key')
            ->where(fn ($q) => $q->whereNull('last_seen_at')->orWhere('last_seen_at', '<', now()->subMinutes(10)))
            ->count();

        $devices = StorageDevice::whereHas('director', $visible)->whereNotNull('total_bytes')->get();
        $storage = [
            'total' => (int) $devices->sum('total_bytes'),
            'used' => (int) $devices->sum('used_bytes'),
        ];

        $attention = Run::whereIn('status', ['failed', 'warn'])
            ->whereHas('job.host.director', $visible)
            ->with('job:id,name,host_id', 'job.host:id,name')
            ->latest()->limit(5)->get();

        $runs = Run::whereHas('job.host.director', $visible)
            ->with('job:id,name,host_id', 'job.host:id,name')
            ->latest()->limit(8)->get();

        // 14-day backup activity for the dashboard sparkline. Pulled in one query
        // and bucketed per day in PHP so it stays portable across SQLite/MySQL.
        $since = now()->subDays(13)->startOfDay();
        $recent = Run::whereHas('job.host.director', $visible)
            ->where('created_at', '>=', $since)
            ->get(['created_at', 'status']);

        $activity = collect(range(0, 13))->map(function ($i) use ($recent) {
            $day = now()->subDays(13 - $i)->startOfDay();
            $next = $day->copy()->addDay();
            $onDay = $recent->filter(fn ($r) => $r->created_at >= $day && $r->created_at < $next);

            return [
                'label' => $day->format('M j'),
                'total' => $onDay->count(),
                'success' => $onDay->where('status', 'success')->count(),
                'issues' => $onDay->whereIn('status', ['failed', 'warn'])->count(),
            ];
        })->all();

        $windowTotal = (int) array_sum(array_column($activity, 'total'));
        $windowSuccess = (int) array_sum(array_column($activity, 'success'));
        $successRate = $windowTotal ? (int) round($windowSuccess / $windowTotal * 100) : null;
        $storageDeviceCount = $devices->count();

        return view('dashboard', compact(
            'stats', 'runs', 'failed24h', 'staleHosts', 'storage', 'attention',
            'activity', 'windowTotal', 'successRate', 'storageDeviceCount',
        ));
    }
}
