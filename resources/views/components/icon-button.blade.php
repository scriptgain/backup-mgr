@props(['href' => null, 'icon', 'title' => null, 'variant' => 'secondary', 'type' => 'button'])
@php
    $variants = [
        'secondary' => 'bg-white ring-1 ring-inset ring-slate-300 text-slate-600 hover:bg-slate-50 hover:text-slate-900',
        'ghost' => 'text-slate-500 hover:bg-slate-100 hover:text-slate-900',
        'danger' => 'bg-white ring-1 ring-inset ring-rose-200 text-rose-600 hover:bg-rose-50 hover:ring-rose-300',
        'brand' => 'bg-brand-50 ring-1 ring-inset ring-brand-200 text-brand-700 hover:bg-brand-100',
    ];
    $classes = 'inline-flex items-center justify-center w-9 h-9 rounded-lg transition ' . ($variants[$variant] ?? $variants['secondary']);
@endphp
{{-- Styled tooltip teleported to <body> with fixed positioning, so it's fully
     outside any table's overflow-x-auto and can never expand horizontal scroll. --}}
<span class="inline-flex"
    @if ($title)
        x-data="{ tip: false, tx: 0, ty: 0 }"
        @mouseenter="const r = $el.getBoundingClientRect(); tx = r.left + r.width / 2; ty = r.top - 8; tip = true"
        @mouseleave="tip = false"
    @endif>
    @if ($href)
        <a href="{{ $href }}" @if ($title) aria-label="{{ $title }}" @endif {{ $attributes->merge(['class' => $classes]) }}>
            <x-icon :name="$icon" class="w-4 h-4" />
        </a>
    @else
        <button type="{{ $type }}" @if ($title) aria-label="{{ $title }}" @endif {{ $attributes->merge(['class' => $classes]) }}>
            <x-icon :name="$icon" class="w-4 h-4" />
        </button>
    @endif
    @if ($title)
        <template x-teleport="body">
            <div x-show="tip" x-cloak :style="`left:${tx}px;top:${ty}px`"
                class="fixed -translate-x-1/2 -translate-y-full pointer-events-none z-[100] whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs font-medium text-white shadow-lg">
                {{ $title }}
            </div>
        </template>
    @endif
</span>
