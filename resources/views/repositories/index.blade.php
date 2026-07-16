@php $backendLabel = ['s3' => 'S3', 'filesystem' => 'Filesystem', 'sftp' => 'SFTP']; @endphp
<x-layouts.app title="Repositories">
    <x-page-header title="Repositories" icon="cloud" subtitle="Where backups are stored. Encrypted and deduplicated by kopia.">
        <x-slot:actions>
            <x-button variant="secondary" icon="settings" href="{{ route('settings.index') }}">Settings</x-button>
            <x-button icon="plus" href="{{ route('repositories.create') }}">New Repository</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($repositories->isEmpty())
        <x-card>
            <x-empty-state icon="cloud" title="No Repositories Yet" description="Add an S3 or filesystem repository, then point backup jobs at it.">
                <x-slot:action><x-button icon="plus" href="{{ route('repositories.create') }}">New Repository</x-button></x-slot:action>
            </x-empty-state>
        </x-card>
    @else
        <x-table>
            <thead>
                <tr><th>Name</th><th>Backend</th><th>Target</th><th>Compression</th><th>Director</th><th class="text-right">Actions</th></tr>
            </thead>
            <tbody>
                @foreach ($repositories as $r)
                    @php $c = $r->config ?? []; $target = $r->backend === 's3' ? (($c['bucket'] ?? '?') . (isset($c['prefix']) ? '/' . $c['prefix'] : '')) : ($c['path'] ?? '—'); @endphp
                    <tr>
                        <td class="font-medium text-slate-900"><a href="{{ route('repositories.show', $r) }}" class="hover:text-brand-700">{{ $r->name }}</a></td>
                        <td><x-badge color="neutral">{{ $backendLabel[$r->backend] ?? $r->backend }}</x-badge></td>
                        <td class="text-slate-500 font-mono text-xs break-all">{{ $target }}</td>
                        <td>{{ $r->compression }}</td>
                        <td>{{ $r->director?->name ?? 'Any' }}</td>
                        <td class="text-right">
                            <div class="inline-flex items-center gap-2">
                                <x-icon-button :href="route('repositories.show', $r)" icon="eye" title="Open" />
                                <x-icon-button :href="route('repositories.edit', $r)" icon="edit" title="Edit" />
                                <x-delete-button :name="'del-repo-' . $r->id" :action="route('repositories.destroy', $r)"
                                    title="Delete Repository?" message="Jobs using this repository will lose their target. Stored backups are not deleted." />
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
    @endif
</x-layouts.app>
