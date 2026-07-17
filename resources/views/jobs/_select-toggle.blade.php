{{-- Per-row toggle switch. Lives inside an Alpine scope exposing `selected`; expects $id. --}}
<button type="button" role="switch"
    :aria-checked="selected.includes({{ $id }}).toString()"
    x-on:click="selected.includes({{ $id }}) ? selected.splice(selected.indexOf({{ $id }}), 1) : selected.push({{ $id }}); confirming = false"
    :class="selected.includes({{ $id }}) ? 'bg-brand-600' : 'bg-slate-300'"
    class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors align-middle"
    aria-label="Select run">
    <span :class="selected.includes({{ $id }}) ? 'translate-x-6' : 'translate-x-1'"
        class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"></span>
</button>
