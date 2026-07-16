@props(['run'])
@php
    $name = 'restore-' . $run->id;
    $origin = ($run->job?->source['root'] ?? '') ?: '/var/restore';
@endphp
<x-icon-button type="button" icon="restore" title="Restore" variant="brand"
    x-data x-on:click="$dispatch('open-modal', '{{ $name }}')" />

<x-modal :name="$name" title="Restore Snapshot" icon="restore" maxWidth="max-w-lg">
    <p>Restore <span class="font-mono text-xs">{{ \Illuminate\Support\Str::limit($run->snapshot_id, 20) }}</span>
       from <span class="font-medium">{{ $run->job?->name }}</span> to a path on
       <span class="font-medium">{{ $run->job?->host?->name }}</span>.</p>
    <form method="POST" action="{{ route('restores.store') }}" class="mt-4 space-y-3" id="restore-form-{{ $run->id }}">
        @csrf
        <input type="hidden" name="run_id" value="{{ $run->id }}">
        <x-field label="Target Path" for="target_path_{{ $run->id }}" hint="Prefilled with the original location. Must be a full path (starts with /). Change it to restore elsewhere.">
            <x-input id="target_path_{{ $run->id }}" name="target_path" :value="old('target_path', $origin)" placeholder="/var/restore" required />
        </x-field>
    </form>
    <x-slot:footer>
        <x-button variant="secondary" size="sm" x-on:click="$dispatch('close-modal', '{{ $name }}')">Cancel</x-button>
        <x-button variant="secondary" size="sm" icon="settings" href="{{ route('restores.create', $run) }}">Advanced</x-button>
        <x-button variant="primary" size="sm" icon="restore" type="submit" form="restore-form-{{ $run->id }}">Queue Restore</x-button>
    </x-slot:footer>
</x-modal>
