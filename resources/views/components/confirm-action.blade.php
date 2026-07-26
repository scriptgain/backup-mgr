@props([
    'name',
    'action',
    'method' => 'POST',
    'title' => 'Are You Sure?',
    'message' => '',
    'confirm' => 'Confirm',
    'confirmIcon' => null,
    'confirmVariant' => 'primary',
    'tone' => 'default',
    // Extra payload for the confirm form, as name => value. Lets one endpoint
    // back several buttons (e.g. which repository, which kind of task) without
    // the caller hand-rolling a form outside the modal.
    'fields' => [],
])
{{-- Wraps a trigger (passed as the default slot) so any action goes through a
     modal confirm instead of firing immediately. --}}
<span x-data @click="$dispatch('open-modal', '{{ $name }}')" class="inline-flex">{{ $slot }}</span>

<x-modal :name="$name" :title="$title" :icon="$tone === 'danger' ? 'warning' : 'info'" :tone="$tone" maxWidth="max-w-md">
    {{ $message }}
    <x-slot:footer>
        <x-button variant="secondary" size="sm" x-on:click="$dispatch('close-modal', '{{ $name }}')">Cancel</x-button>
        <form method="POST" action="{{ $action }}">
            @csrf
            @if ($method !== 'POST')@method($method)@endif
            @foreach ($fields as $fieldName => $fieldValue)
                <input type="hidden" name="{{ $fieldName }}" value="{{ $fieldValue }}">
            @endforeach
            <x-button :variant="$confirmVariant" size="sm" :icon="$confirmIcon" type="submit">{{ $confirm }}</x-button>
        </form>
    </x-slot:footer>
</x-modal>
