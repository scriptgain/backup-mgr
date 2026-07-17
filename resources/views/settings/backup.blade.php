@php $g = fn ($k) => \App\Models\Setting::get($k); @endphp
<x-layouts.app title="Backup & Restore">
    <x-page-header title="Backup & Restore" icon="archive" subtitle="Back up this panel's configuration and restore it later.">
        <x-slot:actions>
            <x-button variant="secondary" icon="settings" href="{{ route('settings.index') }}">Settings</x-button>
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <div class="mb-5 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-800 ring-1 ring-brand-100">{{ session('status') }}</div>
    @endif

    <div class="space-y-6">
        <x-card title="Configuration Backup" subtitle="A JSON snapshot of every panel setting: branding, notifications, integrations, license, and more.">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-slate-500">
                    @if ($g('last_config_backup_at'))
                        Last downloaded {{ \Illuminate\Support\Carbon::parse($g('last_config_backup_at'))->diffForHumans() }}.
                    @else
                        No configuration backup downloaded yet.
                    @endif
                </p>
                <x-button icon="download" href="{{ route('settings.backup.config') }}">Download Config</x-button>
            </div>
        </x-card>

        <x-card title="Full Database Snapshot" subtitle="A complete restore point of the entire panel database.">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-slate-500">Downloads a compressed SQL dump. Keep it somewhere safe; restore it with your database tools for a full rebuild.</p>
                <x-button variant="secondary" icon="download" href="{{ route('settings.backup.database') }}">Download Database</x-button>
            </div>
        </x-card>

        <x-card title="Restore Configuration" subtitle="Upload a configuration backup to re-apply its settings to this panel.">
            <form method="POST" action="{{ route('settings.backup.restore') }}" enctype="multipart/form-data"
                  x-data="{ confirming: false }" x-on:submit="if (! confirming) { $event.preventDefault(); confirming = true; }">
                @csrf
                <div class="mb-4 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-100">
                    Restoring overwrites current settings with the values in the file. Download a fresh backup first.
                </div>
                <x-field label="Backup File" for="backup" hint="A .json configuration backup exported from this panel.">
                    <input type="file" id="backup" name="backup" accept="application/json,.json" required
                        class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200">
                </x-field>
                <div class="mt-4 flex items-center gap-2">
                    <template x-if="! confirming"><x-button type="submit" variant="secondary" icon="refresh">Restore Configuration</x-button></template>
                    <template x-if="confirming">
                        <span class="flex flex-wrap items-center gap-2">
                            <span class="text-sm text-amber-800">Overwrite current settings with this file?</span>
                            <x-button type="button" variant="secondary" size="sm" x-on:click="confirming = false">Cancel</x-button>
                            <x-button type="submit" variant="danger" size="sm" icon="check">Confirm Restore</x-button>
                        </span>
                    </template>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.app>
