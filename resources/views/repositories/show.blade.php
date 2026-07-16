@php $c = $repository->config ?? []; @endphp
<x-layouts.app :title="$repository->name">
    <x-page-header :title="$repository->name" icon="cloud" :subtitle="ucfirst($repository->backend) . ' repository'">
        <x-slot:actions>
            <x-button variant="secondary" icon="edit" href="{{ route('repositories.edit', $repository) }}">Edit</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card title="Details">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
            <div><dt class="text-slate-500">Backend</dt><dd class="mt-0.5 font-medium text-slate-900">{{ ucfirst($repository->backend) }}</dd></div>
            <div><dt class="text-slate-500">Compression</dt><dd class="mt-0.5 font-medium text-slate-900">{{ $repository->compression }}</dd></div>
            <div><dt class="text-slate-500">Director</dt><dd class="mt-0.5 font-medium text-slate-900">{{ $repository->director?->name ?? 'Any' }}</dd></div>
            <div><dt class="text-slate-500">Status</dt><dd class="mt-0.5"><x-badge color="success" dot>{{ ucfirst($repository->status) }}</x-badge></dd></div>
            @if ($repository->backend === 's3')
                <div><dt class="text-slate-500">Endpoint</dt><dd class="mt-0.5 font-medium text-slate-900 break-all">{{ $c['endpoint'] ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Bucket</dt><dd class="mt-0.5 font-medium text-slate-900 break-all">{{ ($c['bucket'] ?? '—') . (isset($c['prefix']) ? '/' . $c['prefix'] : '') }}</dd></div>
                <div><dt class="text-slate-500">Region</dt><dd class="mt-0.5 font-medium text-slate-900">{{ $c['region'] ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Access Key</dt><dd class="mt-0.5 font-medium text-slate-900">{{ $repository->access_key_id ? Str::mask($repository->access_key_id, '*', 3) : '—' }}</dd></div>
            @else
                <div><dt class="text-slate-500">Path</dt><dd class="mt-0.5 font-medium text-slate-900 break-all">{{ $c['path'] ?? '—' }}</dd></div>
            @endif
        </dl>
        <p class="mt-5 text-xs text-slate-500">Secrets (access key, secret, repository password) are encrypted at rest and never shown.</p>
    </x-card>

    @php
        $fmt = function ($b) { if ($b === null) return '—'; $u=['B','KB','MB','GB','TB']; $i=0; while($b>=1024&&$i<4){$b/=1024;$i++;} return round($b,$i?1:0).' '.$u[$i]; };
    @endphp
    <x-card title="Snapshots In This Repository" class="mt-6" :flush="$snapshots->isNotEmpty()">
        <x-slot:actions>
            <x-badge color="neutral">{{ $snapshots->count() }}{{ $snapshots->count() === 200 ? '+' : '' }}</x-badge>
        </x-slot:actions>
        @if ($snapshots->isEmpty())
            <x-empty-state icon="archive" title="No Snapshots" description="No restore points are stored in this repository. Deleted hosts, jobs, and snapshots no longer appear here." />
        @else
            <x-table flush>
                <thead>
                    <tr><th>Host</th><th>Job</th><th>Snapshot</th><th>Size</th><th>Files</th><th>When</th><th class="text-right">Files</th></tr>
                </thead>
                <tbody>
                    @foreach ($snapshots as $r)
                        <tr>
                            <td class="font-medium text-slate-900">{{ $r->job?->host?->name ?? '—' }}</td>
                            <td>{{ $r->job?->name ?? '—' }}</td>
                            <td class="font-mono text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($r->snapshot_id, 20) }}</td>
                            <td>{{ $fmt($r->bytes_in) }}</td>
                            <td class="tabular">{{ $r->files ?? '—' }}</td>
                            <td class="text-slate-500">{{ $r->created_at?->diffForHumans() }}</td>
                            <td class="text-right">
                                <x-icon-button :href="route('snapshots.browse', $r)" icon="folder" title="Browse Files" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        @endif
    </x-card>
</x-layouts.app>
