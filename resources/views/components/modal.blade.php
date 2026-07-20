@props(['name', 'title' => null, 'subtitle' => null, 'icon' => null, 'tone' => 'default', 'maxWidth' => 'max-w-lg'])
@php
    $toneChip = [
        'default' => 'bg-white text-brand-600 ring-brand-200',
        'danger' => 'bg-white text-rose-600 ring-rose-200',
        'warn' => 'bg-white text-amber-600 ring-amber-200',
    ][$tone] ?? 'bg-white text-brand-600 ring-brand-200';
    $toneHead = [
        'default' => 'from-brand-50',
        'danger' => 'from-rose-50',
        'warn' => 'from-amber-50',
    ][$tone] ?? 'from-brand-50';
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
         class="relative flex flex-col w-full {{ $maxWidth }} max-h-[85vh] bg-white rounded-2xl shadow-2xl ring-1 ring-slate-200 overflow-hidden text-left">
        {{-- Header: subtle branded gradient, icon chip, wrapping title + optional subtitle. --}}
        <div class="flex items-start gap-3.5 px-5 py-4 border-b border-slate-100 bg-gradient-to-br {{ $toneHead }} via-white to-white shrink-0">
            @if ($icon)
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl ring-1 shadow-sm shrink-0 {{ $toneChip }}">
                    <x-icon :name="$icon" class="w-5 h-5" />
                </span>
            @endif
            <div class="min-w-0 flex-1">
                <h3 class="text-base font-semibold text-slate-900 leading-snug break-words">{{ $title }}</h3>
                @if ($subtitle)<p class="mt-0.5 text-xs text-slate-500 leading-relaxed break-words">{{ $subtitle }}</p>@endif
            </div>
            <button type="button" @click="open = false" class="shrink-0 -mr-1 -mt-1 text-slate-400 hover:text-slate-600 rounded-lg p-1">
                <x-icon name="x" class="w-5 h-5" />
            </button>
        </div>
        <div class="vx-scroll flex-1 overflow-y-auto px-5 py-4 text-sm text-slate-600 leading-relaxed">
            {{ $slot }}
        </div>
        @isset($footer)
            <div class="flex items-center justify-end gap-2 px-5 py-3.5 border-t border-slate-100 bg-slate-50/70 shrink-0">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
