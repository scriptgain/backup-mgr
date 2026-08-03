@props([
    'action',
    'name',
    'title' => 'Delete?',
    'message' => 'This action cannot be undone.',
    'confirm' => 'Delete',
    'label' => 'Delete',
])
{{-- Icon-button trigger + a danger-toned confirm modal (never a native dialog).
     Tooltip is teleported to <body> (fixed position) so it never expands a
     table's horizontal scroll on hover. --}}
<span class="inline-flex" x-data="{ tip: false, tx: 0, ty: 0 }"
    @mouseenter="const r = $el.getBoundingClientRect(); tx = r.left + r.width / 2; ty = r.top - 8; tip = true"
    @mouseleave="tip = false">
    <button type="button" @click="$dispatch('open-modal', '{{ $name }}')"
        aria-label="{{ $label }}"
        {{ $attributes->merge(['class' => 'inline-flex items-center justify-center w-9 h-9 rounded-lg bg-white ring-1 ring-inset ring-rose-200 text-rose-600 transition hover:bg-rose-50 hover:ring-rose-300']) }}>
        <x-icon name="trash" class="w-4 h-4" />
    </button>
    <template x-teleport="body">
        <div x-show="tip" x-cloak :style="`left:${tx}px;top:${ty}px`"
            class="fixed -translate-x-1/2 -translate-y-full pointer-events-none z-[100] whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs font-medium text-white shadow-lg">
            {{ $label }}
        </div>
    </template>
</span>

<x-modal :name="$name" :title="$title" icon="warning" tone="danger" maxWidth="max-w-md">
    {{ $message }}
    <x-slot:footer>
        <x-button variant="secondary" size="sm" x-on:click="$dispatch('close-modal', '{{ $name }}')">Cancel</x-button>
        {{-- Deleting is a full page POST and can take a moment. Without feedback
             the modal just sits there and the operator clicks again, so the
             button reports that it is working and refuses a second submit.
             Inline x-data on purpose: a JS file calling Alpine.data() would have
             to load before the Alpine CDN, see the note in tailwind-cdn. --}}
        <form method="POST" action="{{ $action }}" x-data="{ busy: false }" @submit="busy = true">
            @csrf
            @method('DELETE')
            {{ $slot }}
            <x-button variant="danger" size="sm" type="submit" icon="trash"
                x-bind:disabled="busy" x-bind:class="busy && 'opacity-70 cursor-not-allowed'">
                <span x-show="!busy">{{ $confirm }}</span>
                <span x-show="busy" x-cloak class="inline-flex items-center gap-2">
                    <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z" />
                    </svg>
                    Removing
                </span>
            </x-button>
        </form>
    </x-slot:footer>
</x-modal>
