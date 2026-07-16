<x-layouts.app title="Shares">
    <x-page-header title="Shares" icon="folder" subtitle="Host and share files publicly, CDN-style.">
        <x-slot:actions>
            <x-button variant="primary" icon="plus" href="{{ route('shares.create') }}">New Share</x-button>
        </x-slot:actions>
    </x-page-header>


    @if ($shares->isEmpty())
        <x-card>
            <x-empty-state icon="folder" title="No Shares Yet"
                description="Create a share, upload files, and get a public link or a static-hosting URL you can hand out anywhere." />
        </x-card>
    @else
        <x-card flush>
            <x-table flush>
                <thead>
                    <tr>
                        <th>Name</th><th>Link</th><th>Access</th><th>Downloads</th><th>Expires</th><th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody x-data>
                    @foreach ($shares as $share)
                        @php $link = $share->isPublic() ? $share->publicUrl() : $share->linkUrl(); @endphp
                        <tr>
                            <td class="font-medium text-slate-900">{{ $share->name }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <a href="{{ $link }}" target="_blank" class="font-mono text-xs text-brand-700 hover:underline truncate max-w-[16rem]">{{ $link }}</a>
                                    <button type="button" title="Copy link" @click="navigator.clipboard.writeText('{{ $link }}')" class="text-slate-400 hover:text-brand-600">
                                        <x-icon name="archive" class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                            <td>
                                @if ($share->isPublic())
                                    <x-badge tone="success">Public</x-badge>
                                @else
                                    <x-badge tone="neutral">Link only</x-badge>
                                @endif
                                @if ($share->password)<x-badge tone="warn">Password</x-badge>@endif
                            </td>
                            <td class="text-slate-600">{{ number_format($share->downloads) }}</td>
                            <td class="text-slate-500 text-sm">
                                @if ($share->expires_at)
                                    <span class="{{ $share->isExpired() ? 'text-rose-600' : '' }}">{{ $share->expires_at->diffForHumans() }}</span>
                                @else
                                    Never
                                @endif
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-1.5">
                                    <x-icon-button :href="route('shares.show', $share)" icon="folder" title="Browse & Upload" variant="brand" />
                                    <x-icon-button :href="route('shares.edit', $share)" icon="edit" title="Edit" />
                                    <x-delete-button :name="'del-share-' . $share->id" :action="route('shares.destroy', $share)"
                                        title="Delete Share?" message="This removes the share link. Tick nothing to keep the files on disk; the confirm form deletes the record only." />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        </x-card>
    @endif
</x-layouts.app>
