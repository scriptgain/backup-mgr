@php
    $statusColor = ['online' => 'success', 'offline' => 'danger', 'pending' => 'warn', 'stale' => 'warn'];
    $connLabel = ['agent' => 'Agent', 'ssh' => 'SSH', 'sftp' => 'SFTP', 'ftp' => 'FTP', 'rsync' => 'Rsync', 's3' => 'S3'];
@endphp
<x-layouts.app title="Hosts">
    <x-page-header title="Hosts" icon="server" subtitle="Every protected machine across all Directors.">
        <x-slot:actions>
            <x-button variant="secondary" icon="cloud" href="{{ route('directors.index') }}">Directors</x-button>
            @if ($directors->count())
                <x-dropdown align="right" width="w-56">
                    <x-slot:trigger>
                        <x-button icon="plus">Add Host</x-button>
                    </x-slot:trigger>
                    <p class="px-3 py-2 text-xs font-medium uppercase tracking-wide text-slate-400">Add To Director</p>
                    @foreach ($directors as $d)
                        <x-dropdown-item :href="route('hosts.create', $d)" icon="cloud">{{ $d->name }}</x-dropdown-item>
                    @endforeach
                </x-dropdown>
            @else
                <x-button icon="plus" href="{{ route('directors.create') }}">Create a Director First</x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($hosts->isEmpty())
        <x-card>
            <x-empty-state icon="server" title="No Hosts Yet" description="Add hosts from within a Director.">
                <x-slot:action><x-button icon="cloud" href="{{ route('directors.index') }}">Go to Directors</x-button></x-slot:action>
            </x-empty-state>
        </x-card>
    @else
        <x-table>
            <thead>
                <tr><th>Name</th><th>Director</th>@if (auth()->user()->isAdmin())<th>Owner</th>@endif<th>Connection</th><th>Target</th><th>Status</th><th class="text-right">Actions</th></tr>
            </thead>
            <tbody>
                @foreach ($hosts as $h)
                    <tr>
                        <td class="font-medium text-slate-900"><a href="{{ route('hosts.show', $h) }}" class="hover:text-brand-700">{{ $h->name }}</a></td>
                        <td>{{ $h->director?->name ?? '—' }}</td>
                        @if (auth()->user()->isAdmin())<td class="text-slate-500">{{ $h->owner?->name ?? 'Inherited' }}</td>@endif
                        <td><x-badge color="neutral">{{ $connLabel[$h->connection_type] ?? $h->connection_type }}</x-badge></td>
                        <td class="text-slate-500">{{ $h->ip_address ?: ($h->hostname ?: '—') }}</td>
                        <td><x-badge :color="$statusColor[$h->effective_status] ?? 'neutral'" dot>{{ ucfirst($h->effective_status) }}</x-badge></td>
                        <td class="text-right">
                            <div class="inline-flex items-center gap-2">
                                <x-icon-button :href="route('hosts.show', $h)" icon="eye" title="Open" />
                                <x-icon-button :href="route('hosts.edit', $h)" icon="edit" title="Edit" />
                                <x-delete-button :name="'del-host-' . $h->id" :action="route('hosts.destroy', $h)"
                                    title="Remove Host?" message="This removes the host, its jobs, and their run history — those snapshots stop being listed here. Data already written to the repository is not removed." />
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
    @endif
</x-layouts.app>
