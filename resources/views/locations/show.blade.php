@php $statusColor = ['online' => 'success', 'offline' => 'danger', 'pending' => 'warn']; @endphp
<x-layouts.app :title="$location->name">
    <x-page-header :title="$location->name" icon="folder"
        :subtitle="collect([$location->address, $location->region])->filter()->implode(' · ') ?: 'Location'">
        <x-slot:actions>
            <x-button variant="secondary" icon="edit" href="{{ route('locations.edit', $location) }}">Edit</x-button>
            <x-button icon="plus" href="{{ route('directors.create', ['location' => $location->id]) }}">Add Director</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card title="Directors" subtitle="Nodes running in this location" :flush="$location->directors->isNotEmpty()">
        @if ($location->directors->isEmpty())
            <x-empty-state icon="cloud" title="No Directors Here" description="Add a Director node to this location.">
                <x-slot:action><x-button icon="plus" href="{{ route('directors.create', ['location' => $location->id]) }}">Add Director</x-button></x-slot:action>
            </x-empty-state>
        @else
            <x-table flush>
                <thead><tr><th>Name</th><th>Address</th><th>Hosts</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
                <tbody>
                    @foreach ($location->directors as $d)
                        <tr>
                            <td class="font-medium text-slate-900"><a href="{{ route('directors.show', $d) }}" class="hover:text-brand-700">{{ $d->name }}</a></td>
                            <td class="text-slate-500">{{ $d->hostname ? $d->hostname . ($d->port ? ':' . $d->port : '') : '—' }}</td>
                            <td class="tabular">{{ $d->hosts_count }}</td>
                            <td><x-badge :color="$statusColor[$d->status] ?? 'neutral'" dot>{{ ucfirst($d->status) }}</x-badge></td>
                            <td class="text-right"><x-icon-button :href="route('directors.show', $d)" icon="eye" title="Open" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        @endif
    </x-card>
</x-layouts.app>
