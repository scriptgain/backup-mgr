@props(['href' => '#', 'icon' => null, 'active' => false])
@php
    $classes = $active
        ? 'text-brand-700 bg-brand-50 ring-1 ring-inset ring-brand-200'
        : 'text-slate-600 ring-1 ring-inset ring-transparent hover:text-slate-900 hover:bg-slate-100 hover:ring-slate-200';
@endphp
<a href="{{ $href }}"
   @if ($active) aria-current="page" @endif
   {{ $attributes->merge(['class' => "inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition $classes"]) }}>
    @if ($icon)<x-icon :name="$icon" class="w-4 h-4 shrink-0" />@endif
    {{ $slot }}
</a>
