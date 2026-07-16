@props(['label', 'value', 'icon' => null, 'trend' => null, 'trendColor' => 'success'])
@php
    $trendClasses = [
        'success' => 'text-emerald-600',
        'danger' => 'text-rose-600',
        'neutral' => 'text-slate-500',
    ][$trendColor] ?? 'text-slate-500';
@endphp
<div {{ $attributes->merge(['class' => 'bg-white rounded-xl ring-1 ring-slate-200 shadow-sm p-5']) }}>
    <div class="flex items-center gap-3">
        @if ($icon)
            {{-- light-bg icon chip gets a border (house style) --}}
            <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-brand-50 text-brand-600 ring-1 ring-brand-100">
                <x-icon :name="$icon" class="w-5 h-5" />
            </span>
        @endif
        <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
    </div>
    <div class="mt-3 flex items-baseline gap-2">
        <span class="text-2xl font-semibold text-slate-900 tabular">{{ $value }}</span>
        @if ($trend)<span class="text-xs font-medium {{ $trendClasses }}">{{ $trend }}</span>@endif
    </div>
</div>
