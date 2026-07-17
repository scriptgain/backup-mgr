@php $badge = ['queued' => 'neutral', 'running' => 'info', 'success' => 'success', 'failed' => 'danger']; @endphp
<x-layouts.app title="Restores">
    <x-page-header title="Restores" icon="restore" subtitle="Restore jobs and their status.">
        <x-slot:actions>
            <x-button variant="secondary" icon="archive" href="{{ route('snapshots.index') }}">Snapshots</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($restores->isEmpty())
        <x-card>
            <x-empty-state icon="restore" title="No Restores Yet" description="Start a restore from the Snapshots page.">
                <x-slot:action><x-button icon="archive" href="{{ route('snapshots.index') }}">Go to Snapshots</x-button></x-slot:action>
            </x-empty-state>
        </x-card>
    @else
        <div x-data="{ selected: [], confirming: false, allIds: [{{ $restores->pluck('id')->implode(',') }}], submitBulk() { const f = this.$refs.bulkForm; f.querySelectorAll('input.js-dyn').forEach(n => n.remove()); this.selected.forEach(id => { const i = document.createElement('input'); i.type='hidden'; i.name='ids[]'; i.value=id; i.className='js-dyn'; f.appendChild(i); }); f.submit(); } }"
             class="rounded-xl ring-1 ring-slate-200 bg-white shadow-sm overflow-hidden">
            <form method="POST" action="{{ route('restores.bulk-destroy') }}" x-ref="bulkForm" class="hidden">@csrf @method('DELETE')</form>
            <div x-show="selected.length" x-cloak class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-brand-50 px-4 py-2.5">
                <span class="text-sm font-medium text-brand-800"><span x-text="selected.length"></span> selected</span>
                <div class="flex items-center gap-2">
                    <template x-if="! confirming"><x-button type="button" variant="danger" size="sm" icon="trash" x-on:click="confirming = true">Delete Selected</x-button></template>
                    <template x-if="confirming">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm text-brand-800">Delete <span x-text="selected.length"></span> restore(s)?</span>
                            <x-button type="button" variant="secondary" size="sm" x-on:click="confirming = false">Cancel</x-button>
                            <x-button type="button" variant="danger" size="sm" icon="trash" x-on:click="submitBulk()">Confirm Delete</x-button>
                        </div>
                    </template>
                </div>
            </div>
            <x-table flush>
                <thead>
                    <tr>
                        <th class="w-10">@include('jobs._select-all-toggle')</th>
                        <th>Host</th><th>Snapshot</th><th>Target Path</th><th>Status</th><th class="text-right">When</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($restores as $r)
                        <tr>
                            <td>@include('jobs._select-toggle', ['id' => $r->id])</td>
                            <td class="font-medium text-slate-900">{{ $r->host?->name ?? '—' }}</td>
                            <td class="font-mono text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($r->snapshot_id, 18) }}</td>
                            <td class="font-mono text-xs">{{ $r->target_path }}</td>
                            <td><x-badge :color="$badge[$r->status] ?? 'neutral'" dot>{{ ucfirst($r->status) }}</x-badge></td>
                            <td class="text-right text-slate-500">{{ $r->created_at?->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        </div>
    @endif
</x-layouts.app>
