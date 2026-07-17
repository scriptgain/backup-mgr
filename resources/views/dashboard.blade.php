@php
    $badge = ['success' => 'success', 'running' => 'info', 'queued' => 'neutral', 'warn' => 'warn', 'failed' => 'danger'];
    $label = ['success' => 'Success', 'running' => 'Running', 'queued' => 'Queued', 'warn' => 'Warnings', 'failed' => 'Failed'];
    $fmt = function ($bytes) {
        if ($bytes === null) return '—';
        $u = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($u) - 1) { $bytes /= 1024; $i++; }
        return round($bytes, $i ? 1 : 0) . ' ' . $u[$i];
    };
@endphp

<x-layouts.app title="Dashboard">
    <x-page-header title="Dashboard" subtitle="Fleet backup health at a glance.">
        <x-slot:actions>
            <x-button variant="secondary" size="sm" icon="cloud" href="{{ route('directors.index') }}">Directors</x-button>
            <x-button size="sm" icon="plus" href="{{ route('directors.index') }}">Add Host</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat label="Directors" value="{{ $stats['directors'] }}" icon="cloud" />
        <x-stat label="Protected Hosts" value="{{ $stats['hosts'] }}" icon="server" />
        <x-stat label="Active Jobs" value="{{ $stats['jobs'] }}" icon="clock" />
        <x-stat label="Restore Points" value="{{ number_format($stats['restore_points']) }}" icon="archive" />
    </div>

    {{-- Fleet health --}}
    <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Failures (24h)</p>
                    <p class="mt-1 text-2xl font-semibold tabular {{ $failed24h ? 'text-rose-600' : 'text-slate-900' }}">{{ $failed24h }}</p>
                </div>
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg ring-1 {{ $failed24h ? 'bg-rose-50 text-rose-600 ring-rose-100' : 'bg-slate-50 text-slate-400 ring-slate-100' }}"><x-icon name="warning" class="w-5 h-5" /></span>
            </div>
        </x-card>
        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Stale Agents</p>
                    <p class="mt-1 text-2xl font-semibold tabular {{ $staleHosts ? 'text-amber-600' : 'text-slate-900' }}">{{ $staleHosts }}</p>
                    <p class="text-xs text-slate-400">Not seen in 10+ min</p>
                </div>
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg ring-1 {{ $staleHosts ? 'bg-amber-50 text-amber-600 ring-amber-100' : 'bg-slate-50 text-slate-400 ring-slate-100' }}"><x-icon name="server" class="w-5 h-5" /></span>
            </div>
        </x-card>
        <x-card>
            <p class="text-sm font-medium text-slate-500">Storage Used</p>
            @if ($storage['total'])
                @php $pct = (int) round($storage['used'] / $storage['total'] * 100); @endphp
                <p class="mt-1 text-sm font-medium text-slate-900 tabular">{{ $fmt($storage['used']) }} / {{ $fmt($storage['total']) }}</p>
                <div class="mt-2 h-2 w-full rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full rounded-full {{ $pct > 90 ? 'bg-rose-500' : 'bg-brand-500' }}" style="width: {{ min(100, $pct) }}%"></div>
                </div>
            @else
                <p class="mt-1 text-sm text-slate-400">No storage devices detected. <a href="{{ route('directors.index') }}" class="text-brand-700 hover:underline">Detect disks</a>.</p>
            @endif
        </x-card>
    </div>

    @if ($attention->isNotEmpty())
        <div class="mt-6">
            <x-card title="Needs Attention" subtitle="Recent failed or warning runs" flush>
                <div x-data="{ selected: [], confirming: false, allIds: [{{ $attention->pluck('id')->implode(',') }}], submitBulk() { const f = this.$refs.bulkForm; f.querySelectorAll('input.js-dyn').forEach(n => n.remove()); this.selected.forEach(id => { const i = document.createElement('input'); i.type='hidden'; i.name='ids[]'; i.value=id; i.className='js-dyn'; f.appendChild(i); }); f.submit(); } }">
                    <form method="POST" action="{{ route('runs.bulk-destroy') }}" x-ref="bulkForm" class="hidden">@csrf @method('DELETE')</form>
                    <div x-show="selected.length" x-cloak class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-brand-50 px-4 py-2.5">
                        <span class="text-sm font-medium text-brand-800"><span x-text="selected.length"></span> selected</span>
                        <div class="flex items-center gap-2">
                            <template x-if="! confirming"><x-button type="button" variant="danger" size="sm" icon="trash" x-on:click="confirming = true">Delete Selected</x-button></template>
                            <template x-if="confirming">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm text-brand-800">Delete <span x-text="selected.length"></span> run(s)?</span>
                                    <x-button type="button" variant="secondary" size="sm" x-on:click="confirming = false">Cancel</x-button>
                                    <x-button type="button" variant="danger" size="sm" icon="trash" x-on:click="submitBulk()">Confirm Delete</x-button>
                                </div>
                            </template>
                        </div>
                    </div>
                    <x-table flush>
                        <thead><tr>
                            <th class="w-10">@include('jobs._select-all-toggle')</th>
                            <th>Host</th><th>Job</th><th>Status</th><th class="text-right">When</th>
                        </tr></thead>
                        <tbody>
                            @foreach ($attention as $r)
                                <tr class="cursor-pointer" onclick="window.location='{{ route('runs.show', $r) }}'">
                                    <td onclick="event.stopPropagation()">@include('jobs._select-toggle', ['id' => $r->id])</td>
                                    <td class="font-medium text-slate-900">{{ $r->job?->host?->name ?? '—' }}</td>
                                    <td>{{ $r->job?->name ?? '—' }}</td>
                                    <td><x-badge :color="$r->status === 'failed' ? 'danger' : 'warn'" dot>{{ ucfirst($r->status) }}</x-badge></td>
                                    <td class="text-right text-slate-500">{{ $r->created_at?->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-table>
                </div>
            </x-card>
        </div>
    @endif

    <div class="mt-6">
        <x-card title="Recent Backup Runs" subtitle="Latest activity across all hosts" :flush="$runs->isNotEmpty()">
            <x-slot:actions>
                <x-button variant="ghost" size="sm" href="{{ route('snapshots.index') }}">View All</x-button>
            </x-slot:actions>

            @if ($runs->isEmpty())
                <x-empty-state icon="archive" title="No Runs Yet" description="Add a host and a backup job, then run it to see activity here.">
                    <x-slot:action><x-button icon="plus" href="{{ route('directors.index') }}">Add a Director</x-button></x-slot:action>
                </x-empty-state>
            @else
                <div x-data="{ selected: [], confirming: false, allIds: [{{ $runs->pluck('id')->implode(',') }}], submitBulk() { const f = this.$refs.bulkForm; f.querySelectorAll('input.js-dyn').forEach(n => n.remove()); this.selected.forEach(id => { const i = document.createElement('input'); i.type='hidden'; i.name='ids[]'; i.value=id; i.className='js-dyn'; f.appendChild(i); }); f.submit(); } }">
                    <form method="POST" action="{{ route('runs.bulk-destroy') }}" x-ref="bulkForm" class="hidden">@csrf @method('DELETE')</form>
                    <div x-show="selected.length" x-cloak class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-brand-50 px-4 py-2.5">
                        <span class="text-sm font-medium text-brand-800"><span x-text="selected.length"></span> selected</span>
                        <div class="flex items-center gap-2">
                            <template x-if="! confirming"><x-button type="button" variant="danger" size="sm" icon="trash" x-on:click="confirming = true">Delete Selected</x-button></template>
                            <template x-if="confirming">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm text-brand-800">Delete <span x-text="selected.length"></span> run(s)?</span>
                                    <x-button type="button" variant="secondary" size="sm" x-on:click="confirming = false">Cancel</x-button>
                                    <x-button type="button" variant="danger" size="sm" icon="trash" x-on:click="submitBulk()">Confirm Delete</x-button>
                                </div>
                            </template>
                        </div>
                    </div>
                    <x-table flush>
                        <thead>
                            <tr><th class="w-10">@include('jobs._select-all-toggle')</th><th>Host</th><th>Job</th><th>Status</th><th>Size</th><th class="text-right">When</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($runs as $r)
                                <tr class="cursor-pointer" onclick="window.location='{{ route('runs.show', $r) }}'">
                                    <td onclick="event.stopPropagation()">@include('jobs._select-toggle', ['id' => $r->id])</td>
                                    <td class="font-medium text-slate-900">{{ $r->job?->host?->name ?? '—' }}</td>
                                    <td>{{ $r->job?->name ?? '—' }}</td>
                                    <td><x-badge :color="$badge[$r->status] ?? 'neutral'" dot>{{ $label[$r->status] ?? ucfirst($r->status) }}</x-badge></td>
                                    <td>{{ $fmt($r->bytes_in) }}</td>
                                    <td class="text-right text-slate-500">{{ $r->created_at?->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-table>
                </div>
            @endif
        </x-card>
    </div>
</x-layouts.app>
