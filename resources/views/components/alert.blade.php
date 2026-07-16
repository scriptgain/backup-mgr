@props(['type' => 'info', 'title' => null])
@php
    $map = [
        'info' => ['bg-brand-50 ring-brand-200 text-brand-800', 'text-brand-600', 'info'],
        'success' => ['bg-emerald-50 ring-emerald-200 text-emerald-800', 'text-emerald-600', 'check-circle'],
        'warn' => ['bg-amber-50 ring-amber-200 text-amber-800', 'text-amber-600', 'warning'],
        'danger' => ['bg-rose-50 ring-rose-200 text-rose-800', 'text-rose-600', 'x-circle'],
    ];
    [$box, $iconColor, $icon] = $map[$type] ?? $map['info'];
@endphp
<div {{ $attributes->merge(['class' => "flex gap-3 rounded-lg ring-1 ring-inset p-4 $box"]) }} role="alert">
    <x-icon :name="$icon" class="w-5 h-5 shrink-0 mt-0.5 {{ $iconColor }}" />
    <div class="text-sm min-w-0">
        @if ($title)<p class="font-semibold">{{ $title }}</p>@endif
        <div class="{{ $title ? 'mt-0.5 opacity-90' : '' }}">{{ $slot }}</div>
    </div>
</div>
