@props(['href' => null, 'icon' => null, 'danger' => false])
@php
    $tone = $danger ? 'text-rose-600 hover:bg-rose-50' : 'text-slate-700 hover:bg-slate-100';
    $classes = "flex w-full items-center gap-2 px-3 py-2 text-sm text-left $tone";
    $tag = $href ? 'a' : 'button';
@endphp
<{{ $tag }} @if ($href) href="{{ $href }}" @else type="button" @endif {{ $attributes->merge(['class' => $classes]) }}>
    @if ($icon)<x-icon :name="$icon" class="w-4 h-4 shrink-0" />@endif
    {{ $slot }}
</{{ $tag }}>
