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
        <x-table>
            <thead>
                <tr><th>Host</th><th>Job</th><th>Snapshot</th><th>Size</th><th>Files</th><th>Status</th><th>When</th><th class="text-right">Actions</th></tr>
            </thead>
            <tbody>
                @foreach ($runs as $r)
                    <tr>
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
        <p class="mt-4 text-xs text-slate-500">File-level browse and restore-from-UI are coming next; for now restores run via the agent/CLI.</p>
    @endif
</x-layouts.app>
