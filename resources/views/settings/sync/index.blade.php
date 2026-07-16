<x-layouts.app title="File Sync">
    <x-page-header title="File Sync" icon="sync" subtitle="Keep a main folder mirrored across multiple hosts.">
        <x-slot:actions>
            <x-button variant="primary" icon="plus" href="{{ route('settings.sync.create') }}">New Sync Folder</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($folders->isEmpty())
        <x-card>
            <x-empty-state icon="sync" title="No Sync Folders Yet"
                description="Pick a main host and folder, then choose the other hosts to keep in sync with it. Backup pushes changes from the main to every target on a schedule." />
        </x-card>
    @else
        <x-card flush>
            <x-table flush>
                <x-slot:head>
                    <tr>
                        <th class="px-4 py-3 text-left">Name</th>
                        <th class="px-4 py-3 text-left">Main Source</th>
                        <th class="px-4 py-3 text-left">Targets</th>
                        <th class="px-4 py-3 text-left">Every</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Last Sync</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </x-slot:head>
                @foreach ($folders as $f)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $f->name }}</td>
                        <td class="px-4 py-3">
                            <div class="text-slate-700">{{ $f->sourceHost?->name }}</div>
                            <div class="font-mono text-xs text-slate-400">{{ $f->source_path }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <x-badge color="neutral">{{ count($f->targets ?? []) }} host{{ count($f->targets ?? []) === 1 ? '' : 's' }}</x-badge>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $f->interval_minutes }}m</td>
                        <td class="px-4 py-3">
                            @php $tone = ['success' => 'success', 'failed' => 'danger', 'running' => 'info', 'idle' => 'neutral'][$f->status] ?? 'neutral'; @endphp
                            @if (! $f->enabled)
                                <x-badge color="neutral">Paused</x-badge>
                            @else
                                <x-badge :color="$tone">{{ ucfirst($f->status) }}</x-badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-500 text-sm">
                            {{ $f->last_synced_at?->diffForHumans() ?? 'Never' }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1.5">
                                <form method="POST" action="{{ route('settings.sync.toggle', $f) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" role="switch" :aria-checked="'{{ $f->enabled ? 'true' : 'false' }}'"
                                        title="{{ $f->enabled ? 'Pause syncing' : 'Resume syncing' }}"
                                        class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors {{ $f->enabled ? 'bg-brand-600' : 'bg-slate-300' }}">
                                        <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform {{ $f->enabled ? 'translate-x-4' : 'translate-x-0.5' }}"></span>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('settings.sync.run', $f) }}">
                                    @csrf
                                    <x-icon-button type="submit" icon="play" title="Sync Now" variant="brand" />
                                </form>
                                <x-icon-button :href="route('settings.sync.edit', $f)" icon="edit" title="Edit" />
                                <x-delete-button :name="'del-sync-' . $f->id" :action="route('settings.sync.destroy', $f)"
                                    title="Delete Sync Folder?"
                                    message="This stops syncing and removes the configuration. Files already synced to targets are left in place." />
                            </div>
                        </td>
                    </tr>
                    @if ($f->last_result)
                        <tr class="border-t border-slate-50">
                            <td colspan="7" class="px-4 py-2 bg-slate-50/60 text-xs text-slate-500 font-mono">{{ \Illuminate\Support\Str::limit($f->last_result, 200) }}</td>
                        </tr>
                    @endif
                @endforeach
            </x-table>
        </x-card>
    @endif
</x-layouts.app>
