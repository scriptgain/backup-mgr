@props(['color' => 'neutral', 'dot' => false])
@php
    $map = [
        'neutral' => ['bg-slate-100 text-slate-700 ring-slate-200', 'bg-slate-400'],
        'info' => ['bg-brand-50 text-brand-700 ring-brand-200', 'bg-brand-500'],
        'success' => ['bg-emerald-50 text-emerald-700 ring-emerald-200', 'bg-emerald-500'],
        'warn' => ['bg-amber-50 text-amber-700 ring-amber-200', 'bg-amber-500'],
        'danger' => ['bg-rose-50 text-rose-700 ring-rose-200', 'bg-rose-500'],
    ];
    [$chip, $dotColor] = $map[$color] ?? $map['neutral'];
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset $chip"]) }}>
    @if ($dot)<span class="w-1.5 h-1.5 rounded-full {{ $dotColor }}"></span>@endif
    {{ $slot }}
</span>
