@props(['align' => 'right', 'width' => 'w-48'])
@php
    $alignment = $align === 'left' ? 'left-0 origin-top-left' : 'right-0 origin-top-right';
@endphp
<div x-data="{ open: false }" class="relative" @click.outside="open = false">
    <div @click="open = !open">
        {{ $trigger }}
    </div>
    <div x-show="open" x-cloak x-transition
         class="absolute z-40 mt-2 {{ $width }} {{ $alignment }} rounded-lg bg-white shadow-lg ring-1 ring-slate-200 py-1"
         @click="open = false">
        {{ $slot }}
    </div>
</div>
