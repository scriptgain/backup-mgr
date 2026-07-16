@props(['name' => null, 'checked' => false, 'label' => null, 'description' => null])
{{-- Toggle switch (never a plain checkbox, per house style). Submits its state
     via a hidden input so it works in normal forms. --}}
<label x-data="{ on: {{ $checked ? 'true' : 'false' }} }" class="flex items-start gap-3 cursor-pointer select-none">
    @if ($name)<input type="hidden" name="{{ $name }}" :value="on ? 1 : 0">@endif
    <button type="button" role="switch" :aria-checked="on.toString()" @click="on = !on"
            :class="on ? 'bg-brand-600' : 'bg-slate-300'"
            class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/60 focus-visible:ring-offset-2">
        <span :class="on ? 'translate-x-6' : 'translate-x-1'"
              class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"></span>
    </button>
    @if ($label || $description)
        <span class="text-sm">
            @if ($label)<span class="font-medium text-slate-900">{{ $label }}</span>@endif
            @if ($description)<span class="block text-slate-500">{{ $description }}</span>@endif
        </span>
    @endif
</label>
