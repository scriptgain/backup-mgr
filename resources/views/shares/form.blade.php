@php $editing = $share->exists; @endphp
<x-layouts.app :title="$editing ? 'Edit Share' : 'New Share'">
    <x-page-header :title="$editing ? 'Edit Share' : 'New Share'" icon="folder"
        subtitle="A share is a folder on disk, served over the web." />

    @if ($errors->any())
        <div class="mb-6"><x-alert type="danger" title="Please fix the highlighted fields">Check the fields below.</x-alert></div>
    @endif

    <form method="POST" action="{{ $editing ? route('shares.update', $share) : route('shares.store') }}"
          x-data="{ vis: '{{ old('visibility', $share->visibility ?: 'private') }}', slug: '{{ old('slug', $share->slug) }}' }">
        @csrf
        @if ($editing) @method('PUT') @endif

        <x-card>
            <x-field label="Share Name" for="name" required :error="$errors->first('name')">
                <x-input id="name" name="name" :value="old('name', $share->name)" placeholder="Marketing assets" />
            </x-field>

            <div class="my-6 border-t border-slate-100"></div>

            <div class="flex items-center gap-2 mb-3">
                <x-icon name="cloud" class="w-4 h-4 text-brand-600" />
                <h3 class="text-sm font-semibold text-slate-900">Your URL</h3>
                <span class="text-xs text-slate-400">Serve this share as your own CDN</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <x-field label="Custom URL" for="slug" :error="$errors->first('slug')" hint="Lowercase letters, numbers, hyphens. Leave blank to auto-generate.">
                    <x-input id="slug" name="slug" x-model="slug" placeholder="my-cdn" />
                    <p class="mt-1.5 text-xs text-slate-400 font-mono truncate">{{ rtrim(url('/s'), '/') }}/<span x-text="slug || '…'"></span></p>
                </x-field>
                <x-field label="Custom Domain" for="custom_domain" :error="$errors->first('custom_domain')"
                    hint="Optional. CNAME it to this server, then serve at your domain root.">
                    <x-input id="custom_domain" name="custom_domain" :value="old('custom_domain', $share->custom_domain)" placeholder="cdn.example.com" />
                </x-field>
            </div>

            <div class="my-6 border-t border-slate-100"></div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <x-field label="Access" for="visibility" required :error="$errors->first('visibility')"
                    hint="Public gets a clean /s/ URL. Link only is reachable via its unguessable link.">
                    <x-select id="visibility" name="visibility" x-model="vis">
                        <option value="private" @selected(old('visibility', $share->visibility) === 'private')>Link only (unlisted)</option>
                        <option value="public" @selected(old('visibility', $share->visibility) === 'public')>Public</option>
                    </x-select>
                </x-field>
                <x-field label="Expires" for="expires_at" :error="$errors->first('expires_at')" hint="Leave blank to never expire.">
                    <x-input id="expires_at" name="expires_at" type="datetime-local"
                        :value="old('expires_at', $share->expires_at?->format('Y-m-d\TH:i'))" />
                </x-field>
            </div>

            <div class="my-6 border-t border-slate-100"></div>

            <div class="space-y-4">
                <x-toggle name="allow_listing" :checked="old('allow_listing', $share->exists ? $share->allow_listing : true)"
                    label="Directory Listing" description="Show a file index when someone opens a folder with no index.html." />
                <x-toggle name="allow_uploads" :checked="old('allow_uploads', $share->allow_uploads)"
                    label="Allow Uploads (panel)" description="Let owners add files to this share from the panel." />
            </div>

            <div class="my-6 border-t border-slate-100"></div>

            <x-field label="Password" for="password" :error="$errors->first('password')"
                hint="{{ $editing && $share->password ? 'Set to change; leave blank to keep the current one.' : 'Optional. Gate the link behind a password.' }}">
                <x-input id="password" name="password" type="password" autocomplete="new-password" placeholder="••••••" />
            </x-field>
            @if ($editing && $share->password)
                <div class="mt-3">
                    <x-toggle name="remove_password" :checked="false" label="Remove Password" description="Make the share open again." />
                </div>
            @endif
        </x-card>

        <div class="mt-6 flex items-center justify-end gap-3">
            <x-button variant="secondary" href="{{ route('shares.index') }}">Cancel</x-button>
            <x-button variant="primary" type="submit" icon="folder">{{ $editing ? 'Save Share' : 'Create Share' }}</x-button>
        </div>
    </form>
</x-layouts.app>
