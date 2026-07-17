@php
    $badge = ['success' => 'success', 'warn' => 'warn', 'failed' => 'danger'];
    $fmt = function ($b) { if ($b === null) return '—'; $u=['B','KB','MB','GB','TB']; $i=0; while($b>=1024&&$i<4){$b/=1024;$i++;} return round($b,$i?1:0).' '.$u[$i]; };
@endphp
<x-layouts.app title="Snapshots">
    <x-page-header title="Snapshots" icon="archive" subtitle="Every backup run that produced a restore point.">
        <x-slot:actions>
            <x-button variant="secondary" icon="clock" href="{{ route('jobs.index') }}">Jobs</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($runs->isEmpty())
        <x-card>
            <x-empty-state icon="archive" title="No Snapshots Yet" description="Run a backup job to create your first restore point.">
                <x-slot:action><x-button icon="clock" href="{{ route('jobs.index') }}">Go to Jobs</x-button></x-slot:action>
            </x-empty-state>
        </x-card>
    @else
        <div x-data="{ selected: [], confirming: false, allIds: [{{ $runs->pluck('id')->implode(',') }}], submitBulk() { const f = this.$refs.bulkForm; f.querySelectorAll('input.js-dyn').forEach(n => n.remove()); this.selected.forEach(id => { const i = document.createElement('input'); i.type='hidden'; i.name='ids[]'; i.value=id; i.className='js-dyn'; f.appendChild(i); }); f.submit(); } }"
             class="rounded-xl ring-1 ring-slate-200 bg-white shadow-sm overflow-hidden">
            <form method="POST" action="{{ route('runs.bulk-destroy') }}" x-ref="bulkForm" class="hidden">@csrf @method('DELETE')</form>
            <div x-show="selected.length" x-cloak class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-brand-50 px-4 py-2.5">
                <span class="text-sm font-medium text-brand-800"><span x-text="selected.length"></span> selected</span>
                <div class="flex items-center gap-2">
                    <template x-if="! confirming"><x-button type="button" variant="danger" size="sm" icon="trash" x-on:click="confirming = true">Delete Selected</x-button></template>
                    <template x-if="confirming">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm text-brand-800">Delete <span x-text="selected.length"></span> snapshot(s)?</span>
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
                        <th>Host</th><th>Job</th><th>Snapshot</th><th>Size</th><th>Files</th><th>Status</th><th>When</th><th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($runs as $r)
                        <tr>
                            <td>@include('jobs._select-toggle', ['id' => $r->id])</td>
                            <td class="font-medium text-slate-900">{{ $r->job?->host?->name ?? '—' }}</td>
                            <td>{{ $r->job?->name ?? '—' }}</td>
                            <td class="font-mono text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($r->snapshot_id, 20) }}</td>
                            <td>{{ $fmt($r->bytes_in) }}</td>
                            <td class="tabular">{{ $r->files ?? '—' }}</td>
                            <td><x-badge :color="$badge[$r->status] ?? 'neutral'" dot>{{ ucfirst($r->status) }}</x-badge></td>
                            <td class="text-slate-500">{{ $r->created_at?->diffForHumans() }}</td>
                            <td class="text-right">
                                <div class="inline-flex items-center gap-2">
                                    @if ($r->snapshot_id)
                                        <x-icon-button :href="route('snapshots.browse', $r)" icon="folder" title="Browse Files" />
                                        <x-restore-button :run="$r" />
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        </div>
        <p class="mt-4 text-xs text-slate-500">File-level browse and restore-from-UI are coming next; for now restores run via the agent/CLI.</p>
    @endif
</x-layouts.app>
