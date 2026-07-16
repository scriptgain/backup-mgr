@php $c = $repository->config ?? []; @endphp
<x-layouts.app title="Edit Repository">
    <x-page-header title="Edit Repository" icon="cloud" :subtitle="$repository->name" />

    <form method="POST" action="{{ route('repositories.update', $repository) }}" x-data="{ tab: 'basics', backend: '{{ old('backend', $repository->backend) }}' }">
        @csrf
        @method('PUT')
        @if ($errors->any())
            <div class="mb-6"><x-alert type="danger" title="Please fix the highlighted fields">Check each tab.</x-alert></div>
        @endif

        <x-card>
            <nav class="flex flex-wrap items-center gap-1 pb-4 mb-5 border-b border-slate-100">
                @foreach (['basics' => 'Basics', 'storage' => 'Storage', 'encryption' => 'Encryption'] as $key => $label)
                    <button type="button" @click="tab='{{ $key }}'" :class="tab==='{{ $key }}' ? 'bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200' : 'text-slate-500 hover:bg-slate-100'" class="px-3 py-1.5 rounded-lg text-sm font-medium transition">{{ $label }}</button>
                @endforeach
            </nav>

            <div x-show="tab==='basics'" class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <x-field label="Name" for="name" required :error="$errors->first('name')">
                    <x-input id="name" name="name" :value="old('name', $repository->name)" required />
                </x-field>
                <x-field label="Director" for="director_id">
                    <x-select id="director_id" name="director_id">
                        <option value="">Any</option>
                        @foreach ($directors as $d)<option value="{{ $d->id }}" @selected(old('director_id', $repository->director_id) == $d->id)>{{ $d->name }}</option>@endforeach
                    </x-select>
                </x-field>
                <x-field label="Backend" for="backend" required>
                    <x-select id="backend" name="backend" x-model="backend">
                        <option value="s3">S3-compatible</option>
                        <option value="filesystem">Filesystem (local to Director)</option>
                    </x-select>
                </x-field>
                <x-field label="Compression" for="compression" required>
                    <x-select id="compression" name="compression">
                        @foreach (['zstd', 'gzip', 's2', 'none'] as $opt)<option value="{{ $opt }}" @selected(old('compression', $repository->compression) === $opt)>{{ $opt }}</option>@endforeach
                    </x-select>
                </x-field>
            </div>

            <div x-show="tab==='storage'" x-cloak>
                <div x-show="backend === 's3'" class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <x-field label="Endpoint" for="endpoint"><x-input id="endpoint" name="endpoint" :value="old('endpoint', $c['endpoint'] ?? '')" /></x-field>
                    <x-field label="Region" for="region"><x-input id="region" name="region" :value="old('region', $c['region'] ?? '')" /></x-field>
                    <x-field label="Bucket" for="bucket"><x-input id="bucket" name="bucket" :value="old('bucket', $c['bucket'] ?? '')" /></x-field>
                    <x-field label="Prefix" for="prefix"><x-input id="prefix" name="prefix" :value="old('prefix', $c['prefix'] ?? '')" /></x-field>
                    <x-field label="Access Key ID" for="access_key_id"><x-input id="access_key_id" name="access_key_id" :value="old('access_key_id', $repository->access_key_id)" autocomplete="off" /></x-field>
                    <x-field label="Secret Access Key" for="secret_access_key" hint="Leave blank to keep."><x-input id="secret_access_key" name="secret_access_key" type="password" autocomplete="new-password" data-lpignore="true" /></x-field>
                </div>
                <div x-show="backend === 'filesystem'" x-cloak>
                    <x-field label="Path" for="path"><x-input id="path" name="path" :value="old('path', $c['path'] ?? '')" /></x-field>
                </div>
            </div>

            <div x-show="tab==='encryption'" x-cloak>
                <div class="max-w-md">
                    <x-field label="Repository Password" for="password" hint="Leave blank to keep the existing password." :error="$errors->first('password')">
                        <x-input id="password" name="password" type="password" autocomplete="new-password" data-lpignore="true" />
                    </x-field>
                </div>
            </div>

            <x-slot:footer>
                <div class="flex items-center justify-end gap-2">
                    <x-button variant="secondary" href="{{ route('repositories.show', $repository) }}">Cancel</x-button>
                    <x-button type="submit" icon="check">Save Changes</x-button>
                </div>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.app>
