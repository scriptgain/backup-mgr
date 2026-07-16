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
        <x-table>
            <thead>
                <tr><th>Host</th><th>Snapshot</th><th>Target Path</th><th>Status</th><th class="text-right">When</th></tr>
            </thead>
            <tbody>
                @foreach ($restores as $r)
                    <tr>
                        <td class="font-medium text-slate-900">{{ $r->host?->name ?? '—' }}</td>
                        <td class="font-mono text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($r->snapshot_id, 18) }}</td>
                        <td class="font-mono text-xs">{{ $r->target_path }}</td>
                        <td><x-badge :color="$badge[$r->status] ?? 'neutral'" dot>{{ ucfirst($r->status) }}</x-badge></td>
                        <td class="text-right text-slate-500">{{ $r->created_at?->diffForHumans() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
    @endif
</x-layouts.app>
