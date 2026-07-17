{{-- Select-all toggle switch. Lives inside an Alpine scope exposing `selected` + `allIds`. --}}
<button type="button" role="switch"
    :aria-checked="(allIds.length > 0 && selected.length === allIds.length).toString()"
    x-on:click="selected = (allIds.length > 0 && selected.length === allIds.length) ? [] : [...allIds]; confirming = false"
    :class="(allIds.length > 0 && selected.length === allIds.length) ? 'bg-brand-600' : 'bg-slate-300'"
    class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors align-middle disabled:opacity-40"
    :disabled="allIds.length === 0" aria-label="Select all">
    <span :class="(allIds.length > 0 && selected.length === allIds.length) ? 'translate-x-6' : 'translate-x-1'"
        class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"></span>
</button>
