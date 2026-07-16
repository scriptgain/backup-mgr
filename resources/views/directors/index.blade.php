@php $statusColor = ['online' => 'success', 'offline' => 'danger', 'pending' => 'warn']; @endphp
<x-layouts.app title="Directors">
    <x-page-header title="Directors" icon="cloud" subtitle="Nodes that run backups. Each Director belongs to a Location and holds many Hosts.">
        <x-slot:actions>
            <x-button variant="secondary" icon="folder" href="{{ route('locations.index') }}">Locations</x-button>
            <x-button icon="plus" href="{{ route('directors.create') }}">New Director</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($directors->isEmpty())
        <x-card>
            <x-empty-state icon="cloud" title="No Directors Yet" description="Create a Director node, then add hosts within it.">
                <x-slot:action><x-button icon="plus" href="{{ route('directors.create') }}">New Director</x-button></x-slot:action>
            </x-empty-state>
        </x-card>
    @else
        <x-table>
            <thead>
                <tr><th>Name</th><th>Location</th><th>Address</th><th>Hosts</th><th>Status</th><th class="text-right">Actions</th></tr>
            </thead>
            <tbody>
                @foreach ($directors as $d)
                    <tr>
                        <td class="font-medium text-slate-900">
                            <a href="{{ route('directors.show', $d) }}" class="hover:text-brand-700">{{ $d->name }}</a>
                            @if ($d->is_local)<x-badge color="info" class="ml-2">Local</x-badge>@endif
                        </td>
                        <td>{{ $d->location?->name ?? '—' }}</td>
                        <td class="text-slate-500 font-mono text-xs">{{ $d->hostname ? $d->hostname . ($d->port ? ':' . $d->port : '') : '—' }}</td>
                        <td class="tabular">{{ $d->hosts_count }}</td>
                        <td><x-badge :color="$statusColor[$d->status] ?? 'neutral'" dot>{{ ucfirst($d->status) }}</x-badge></td>
                        <td class="text-right">
                            <div class="inline-flex items-center gap-2">
                                <x-icon-button :href="route('directors.show', $d)" icon="eye" title="Open" />
                                <x-icon-button :href="route('directors.edit', $d)" icon="edit" title="Edit" />
                                @unless ($d->is_local)
                                    <x-delete-button :name="'del-dir-' . $d->id" :action="route('directors.destroy', $d)"
                                        title="Delete Director?" message="This removes the Director and all its hosts and jobs." />
                                @endunless
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
    @endif
</x-layouts.app>
