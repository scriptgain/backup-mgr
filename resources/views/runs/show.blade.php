@php
    $badge = ['success' => 'success', 'running' => 'info', 'queued' => 'neutral', 'warn' => 'warn', 'failed' => 'danger'];
    $fmt = function ($b) { if ($b === null) return '—'; $u=['B','KB','MB','GB','TB']; $i=0; while($b>=1024&&$i<4){$b/=1024;$i++;} return round($b,$i?1:0).' '.$u[$i]; };
    $dur = ($run->started_at && $run->finished_at) ? $run->started_at->diffForHumans($run->finished_at, true) : '—';
@endphp
<x-layouts.app :title="'Run #' . $run->id">
    <x-page-header :title="'Run #' . $run->id" icon="clock"
        :subtitle="($run->job?->name ?? 'Job') . ' · ' . ($run->job?->host?->name ?? '')">
        <x-slot:actions>
            @if ($run->job)<x-button variant="secondary" icon="clock" href="{{ route('jobs.show', $run->job) }}">Job</x-button>@endif
            @if ($run->snapshot_id)<x-button icon="folder" href="{{ route('snapshots.browse', $run) }}">Browse Files</x-button>@endif
            <x-delete-button :name="'del-run-' . $run->id" :action="route('runs.destroy', $run)"
                title="Delete Run?" message="This removes the run record and its log. The snapshot in the repository is not deleted." confirm="Delete" label="Delete Run" />
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-stat label="Status" :value="ucfirst($run->status)" icon="clock" />
        <x-stat label="Size" :value="$fmt($run->bytes_in)" icon="archive" />
        <x-stat label="Files" :value="$run->files ?? '—'" icon="folder" />
        <x-stat label="Duration" :value="$dur" icon="clock" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <x-card title="Log">
                @if ($run->status === 'failed' && $run->error)
                    <div class="mb-4"><x-alert type="danger" title="This Run Failed">{{ $run->error }}</x-alert></div>
                @endif
                @if ($run->log || $run->error)
                    <pre class="rounded-lg bg-chrome text-slate-100 text-xs p-4 overflow-x-auto whitespace-pre-wrap">{{ $run->log ?: $run->error }}</pre>
                @else
                    <p class="text-sm text-slate-500">No log recorded for this run{{ $run->status === 'queued' ? ' — it has not started yet.' : '.' }}</p>
                @endif
            </x-card>
        </div>
        <div>
            <x-card title="Details">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Status</dt><dd><x-badge :color="$badge[$run->status] ?? 'neutral'" dot>{{ ucfirst($run->status) }}</x-badge></dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Host</dt><dd class="font-medium text-slate-900">{{ $run->job?->host?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Repository</dt><dd class="font-medium text-slate-900">{{ $run->job?->repository?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Snapshot</dt><dd class="font-mono text-xs text-slate-700">@if ($run->snapshot_id)<a href="{{ route('snapshots.browse', $run) }}" class="text-brand-700 hover:text-brand-800 hover:underline">{{ \Illuminate\Support\Str::limit($run->snapshot_id, 16) }}</a>@else—@endif</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Started</dt><dd class="text-slate-700">{{ $run->started_at?->diffForHumans() ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Finished</dt><dd class="text-slate-700">{{ $run->finished_at?->diffForHumans() ?? '—' }}</dd></div>
                </dl>
            </x-card>
        </div>
    </div>
</x-layouts.app>
