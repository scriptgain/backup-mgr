<x-layouts.app title="New Repository">
    <x-page-header title="New Repository" icon="cloud" subtitle="An S3-compatible or filesystem target for encrypted, deduplicated backups." />

    <form method="POST" action="{{ route('repositories.store') }}" x-data="{ tab: 'basics', backend: '{{ old('backend', 's3') }}' }">
        @csrf
        @if ($errors->any())
            <div class="mb-6"><x-alert type="danger" title="Please fix the highlighted fields">Check each tab.</x-alert></div>
        @endif

        <x-card>
            <nav class="flex flex-wrap items-center gap-1 pb-4 mb-5 border-b border-slate-100">
                @foreach (['basics' => 'Basics', 'storage' => 'Storage', 'encryption' => 'Encryption'] as $key => $label)
                    <button type="button" @click="tab='{{ $key }}'" :class="tab==='{{ $key }}' ? 'bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200' : 'text-slate-500 hover:bg-slate-100'" class="px-3 py-1.5 rounded-lg text-sm font-medium transition">{{ $label }}</button>
                @endforeach
                <span class="ml-auto text-xs text-slate-400" x-text="backend === 's3' ? 'S3' : 'Filesystem'"></span>
            </nav>

            <div x-show="tab==='basics'" class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <x-field label="Name" for="name" required :error="$errors->first('name')">
                    <x-input id="name" name="name" :value="old('name')" required autofocus placeholder="e.g. Primary S3" />
                </x-field>
                <x-field label="Director" for="director_id" hint="Optional. Leave blank for any Director.">
                    <x-select id="director_id" name="director_id">
                        <option value="">Any</option>
                        @foreach ($directors as $d)<option value="{{ $d->id }}" @selected(old('director_id') == $d->id)>{{ $d->name }}</option>@endforeach
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
                        @foreach (['zstd', 'gzip', 's2', 'none'] as $c)<option value="{{ $c }}" @selected(old('compression', config('backup.default_compression', 'zstd')) === $c)>{{ $c }}</option>@endforeach
                    </x-select>
                </x-field>
            </div>

            <div x-show="tab==='storage'" x-cloak>
                <div x-show="backend === 's3'" class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <x-field label="Endpoint" for="endpoint" hint="e.g. s3.us-west-1.amazonaws.com or MinIO host" :error="$errors->first('endpoint')"><x-input id="endpoint" name="endpoint" :value="old('endpoint')" placeholder="s3.example.com" /></x-field>
                    <x-field label="Region" for="region"><x-input id="region" name="region" :value="old('region')" placeholder="us-east-1" /></x-field>
                    <x-field label="Bucket" for="bucket"><x-input id="bucket" name="bucket" :value="old('bucket')" placeholder="backup-backups" /></x-field>
                    <x-field label="Prefix" for="prefix" hint="Optional path within the bucket."><x-input id="prefix" name="prefix" :value="old('prefix')" placeholder="host-a/" /></x-field>
                    <x-field label="Access Key ID" for="access_key_id"><x-input id="access_key_id" name="access_key_id" :value="old('access_key_id')" autocomplete="off" /></x-field>
                    <x-field label="Secret Access Key" for="secret_access_key"><x-input id="secret_access_key" name="secret_access_key" type="password" autocomplete="new-password" data-lpignore="true" /></x-field>
                </div>
                <div x-show="backend === 'filesystem'" x-cloak>
                    <x-field label="Path" for="path" hint="Absolute path on the Director host where the repository lives." :error="$errors->first('path')"><x-input id="path" name="path" :value="old('path')" placeholder="/var/backups/backup" /></x-field>
                </div>
            </div>

            <div x-show="tab==='encryption'" x-cloak>
                <x-alert type="info" title="What Is The Repository Password?">
                    kopia encrypts the repository at rest with this password — it is needed to read or restore any backup. Leave it blank and we'll generate a strong one and store it encrypted.
                </x-alert>
                <div class="mt-4 max-w-md">
                    <x-field label="Repository Password (Optional)" for="password" hint="Leave blank to auto-generate." :error="$errors->first('password')">
                        <x-input id="password" name="password" type="password" autocomplete="new-password" data-lpignore="true" />
                    </x-field>
                </div>
            </div>

            <x-slot:footer>
                <div class="flex items-center justify-end gap-2">
                    <x-button variant="secondary" href="{{ route('repositories.index') }}">Cancel</x-button>
                    <x-button type="submit" icon="plus">Create Repository</x-button>
                </div>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.app>
