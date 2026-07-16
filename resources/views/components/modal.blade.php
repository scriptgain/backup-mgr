@props(['name', 'title' => null, 'icon' => null, 'tone' => 'default', 'maxWidth' => 'max-w-lg'])
@php
    $toneChip = [
        'default' => 'bg-brand-50 text-brand-600 ring-brand-100',
        'danger' => 'bg-rose-50 text-rose-600 ring-rose-100',
        'warn' => 'bg-amber-50 text-amber-600 ring-amber-100',
    ][$tone] ?? 'bg-brand-50 text-brand-600 ring-brand-100';
@endphp
{{-- Accessible modal (replaces native confirm/alert/prompt).
     Open:  $dispatch('open-modal', '{{ $name }}')   Close: $dispatch('close-modal', '{{ $name }}') --}}
<div x-data="{ open: false }"
     x-on:open-modal.window="if ($event.detail === '{{ $name }}') open = true"
     x-on:close-modal.window="if ($event.detail === '{{ $name }}') open = false"
     x-on:keydown.escape.window="open = false"
     x-show="open" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div x-show="open" x-transition.opacity.duration.200ms
         class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="open = false"></div>
    <div x-show="open"
         x-trap.inert.noscroll="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="relative w-full {{ $maxWidth }} bg-white rounded-2xl shadow-2xl ring-1 ring-slate-200 overflow-hidden text-left">
        <div class="flex items-start gap-4 px-6 pt-6">
            @if ($icon)
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl ring-1 shrink-0 {{ $toneChip }}">
                    <x-icon :name="$icon" class="w-5 h-5" />
                </span>
            @endif
            <div class="min-w-0 flex-1 pt-0.5">
                <h3 class="text-base font-semibold text-slate-900">{{ $title }}</h3>
            </div>
            <button type="button" @click="open = false" class="text-slate-400 hover:text-slate-600 rounded-lg p-1 -m-1 shrink-0">
                <x-icon name="x" class="w-5 h-5" />
            </button>
        </div>
        <div class="px-6 py-4 {{ $icon ? 'sm:pl-20' : '' }} text-sm text-slate-600 leading-relaxed">
            {{ $slot }}
        </div>
        @isset($footer)
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-slate-100 bg-slate-50/70">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
