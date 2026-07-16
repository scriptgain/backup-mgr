@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
    'icon' => null,
])
@php
    $base = 'inline-flex items-center justify-center gap-2 font-medium rounded-lg transition select-none disabled:opacity-50 disabled:pointer-events-none';
    $variants = [
        'primary' => 'bg-brand-600 text-white hover:bg-brand-700 shadow-sm',
        'secondary' => 'bg-white text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-slate-50',
        'ghost' => 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
        'danger' => 'bg-rose-600 text-white hover:bg-rose-700 shadow-sm',
    ];
    $sizes = [
        'sm' => 'px-3 py-1.5 text-sm',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-5 py-2.5 text-base',
    ];
    $classes = $base . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-icon :name="$icon" class="w-4 h-4 -ml-0.5 shrink-0" />@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-icon :name="$icon" class="w-4 h-4 -ml-0.5 shrink-0" />@endif
        {{ $slot }}
    </button>
@endif
