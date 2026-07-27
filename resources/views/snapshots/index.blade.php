@php
    $badge = ['success' => 'success', 'warn' => 'warn', 'failed' => 'danger'];
    $fmt = function ($b) { if ($b === null) return '—'; $u=['B','KB','MB','GB','TB']; $i=0; while($b>=1024&&$i<4){$b/=1024;$i++;} return round($b,$i?1:0).' '.$u[$i]; };
    // A snapshot already handed to an agent for deletion is not selectable, and
    // must not offer a restore that is about to stop working.
    $selectable = $runs->reject(fn ($r) => in_array($r->snapshot_id, $pendingDeletes, true));
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
        <div x-data="{ selected: [], confirming: false, allIds: [{{ $selectable->pluck('id')->implode(',') }}], submitBulk() { const f = this.$refs.bulkForm; f.querySelectorAll('input.js-dyn').forEach(n => n.remove()); this.selected.forEach(id => { const i = document.createElement('input'); i.type='hidden'; i.name='ids[]'; i.value=id; i.className='js-dyn'; f.appendChild(i); }); f.submit(); } }"
             class="rounded-xl ring-1 ring-slate-200 bg-white shadow-sm overflow-hidden">
            @include('snapshots._bulk-bar')
            <x-table flush>
                <thead>
                    <tr>
                        <th class="w-10">@include('jobs._select-all-toggle')</th>
                        <th>Host</th><th>Job</th><th>Snapshot</th><th>Size</th><th>Files</th><th>Status</th><th>When</th><th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($runs as $r)
                        @php $pending = in_array($r->snapshot_id, $pendingDeletes, true); @endphp
                        <tr>
                            <td>@unless ($pending)@include('jobs._select-toggle', ['id' => $r->id])@endunless</td>
                            <td class="font-medium text-slate-900">{{ $r->job?->host?->name ?? '—' }}</td>
                            <td>{{ $r->job?->name ?? '—' }}</td>
                            <td class="font-mono text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($r->snapshot_id, 20) }}</td>
                            <td>{{ $fmt($r->bytes_in) }}</td>
                            <td class="tabular">{{ $r->files ?? '—' }}</td>
                            <td><x-badge :color="$badge[$r->status] ?? 'neutral'" dot>{{ ucfirst($r->status) }}</x-badge></td>
                            <td class="text-slate-500">{{ $r->created_at?->diffForHumans() }}</td>
                            <td class="text-right">
                                @if ($pending)
                                    <x-badge color="warn" dot>Delete Queued</x-badge>
                                @elseif ($r->snapshot_id)
                                    <div class="inline-flex items-center gap-2">
                                        <x-icon-button :href="route('snapshots.browse', $r)" icon="folder" title="Browse Files" />
                                        <x-restore-button :run="$r" />
                                        @include('snapshots._delete-button', ['run' => $r])
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        </div>
        <p class="mt-4 text-xs text-slate-500">
            Deleting a snapshot removes the backup data from its repository: the agent holding that repository does the
            work on its next check-in, and the restore point disappears once it confirms. To drop a run record without
            touching the data, use the run list on the job.
        </p>
    @endif
</x-layouts.app>
