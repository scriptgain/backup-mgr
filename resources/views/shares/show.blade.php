@php
    $fmt = function ($b) {
        if ($b === null) return '';
        if ($b == 0) return '0 B';
        $u = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log(max($b, 1), 1024));
        return round($b / (1024 ** $i), 1) . ' ' . $u[$i];
    };
    $linkBase = $share->isPublic() ? $share->publicUrl() : $share->linkUrl();
    $segs = $rel === '' ? [] : explode('/', $rel);
@endphp
<x-layouts.app :title="$share->name">
    <x-page-header :title="$share->name" icon="folder" :subtitle="$share->isPublic() ? 'Public share' : 'Link-only share'">
        <x-slot:actions>
            <x-button variant="secondary" icon="eye" href="{{ $linkBase }}" x-data x-bind:target="'_blank'">Open Public</x-button>
            <x-button variant="secondary" icon="edit" href="{{ route('shares.edit', $share) }}">Settings</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($errors->any())
        <div class="mb-6"><x-alert type="danger">{{ $errors->first() }}</x-alert></div>
    @endif

    {{-- Public URL bar --}}
    <div x-data class="mb-6 flex items-center gap-2 rounded-xl bg-slate-50 ring-1 ring-slate-200 px-4 py-3">
        <x-icon name="cloud" class="w-4 h-4 text-slate-400 shrink-0" />
        <span class="font-mono text-sm text-slate-700 truncate flex-1">{{ $linkBase }}</span>
        <x-button variant="secondary" size="sm" icon="archive" x-on:click="navigator.clipboard.writeText('{{ $linkBase }}')">Copy</x-button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <x-card flush>
                <x-slot:title>
                    <span class="text-sm font-normal text-slate-500">
                        <a href="{{ route('shares.show', $share) }}" class="hover:text-brand-700">Home</a>
                        @foreach ($segs as $i => $seg)
                            <span class="text-slate-300">/</span><a href="{{ route('shares.show', ['share' => $share, 'path' => implode('/', array_slice($segs, 0, $i + 1))]) }}" class="hover:text-brand-700">{{ $seg }}</a>
                        @endforeach
                    </span>
                </x-slot:title>
                @if ($entries->isEmpty())
                    <x-empty-state icon="folder" title="Empty" description="Upload files on the right to start hosting." />
                @else
                    <x-table flush>
                        <thead><tr><th>Name</th><th>Size</th><th class="text-right">Actions</th></tr></thead>
                        <tbody>
                            @foreach ($entries as $e)
                                <tr>
                                    <td>
                                        @if ($e->is_dir)
                                            <a href="{{ route('shares.show', ['share' => $share, 'path' => $e->rel]) }}" class="flex items-center gap-2 text-slate-800 hover:text-brand-700">
                                                <x-icon name="folder" class="w-4 h-4 text-slate-400" /> {{ $e->name }}
                                            </a>
                                        @else
                                            <a href="{{ $linkBase }}/{{ $e->rel }}" target="_blank" class="flex items-center gap-2 text-slate-700 hover:text-brand-700">
                                                <x-icon name="archive" class="w-4 h-4 text-slate-300" /> {{ $e->name }}
                                            </a>
                                        @endif
                                    </td>
                                    <td class="text-slate-500 text-sm">{{ $fmt($e->size) }}</td>
                                    <td>
                                        <div class="flex items-center justify-end gap-1.5">
                                            @unless ($e->is_dir)
                                                <x-icon-button :href="$linkBase.'/'.$e->rel.'?dl=1'" icon="restore" title="Download" />
                                            @endunless
                                            <x-delete-button :name="'del-file-' . md5($e->rel)" :action="route('shares.delete-file', $share)"
                                                title="Delete?" message="Permanently delete this item from the share." label="Delete">
                                                <input type="hidden" name="rel" value="{{ $e->rel }}">
                                            </x-delete-button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-table>
                @endif
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card title="Upload">
                <form method="POST" action="{{ route('shares.upload', $share) }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <input type="hidden" name="path" value="{{ $rel }}">
                    <input type="file" name="files[]" multiple required
                        class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100">
                    <x-button variant="primary" size="sm" type="submit" icon="cloud" class="w-full justify-center">Upload Here</x-button>
                </form>
            </x-card>

            <x-card title="New Folder">
                <form method="POST" action="{{ route('shares.folder', $share) }}" class="flex items-center gap-2">
                    @csrf
                    <input type="hidden" name="path" value="{{ $rel }}">
                    <x-input name="name" placeholder="assets" />
                    <x-button variant="secondary" size="sm" type="submit" icon="plus">Add</x-button>
                </form>
            </x-card>
        </div>
    </div>
</x-layouts.app>
