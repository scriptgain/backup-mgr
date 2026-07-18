@props(['title', 'subtitle' => null, 'icon' => null, 'back' => null])
{{-- Slim page header (house style). Optional breadcrumb-style back link above
     the title; title + optional subtitle on the left, actions slot on the right. --}}
<div {{ $attributes->merge(['class' => 'pb-5']) }}>
    @if ($back)
        <div class="flex justify-end mb-3">
            <a href="{{ $back['href'] }}" class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 text-sm font-medium text-slate-600 shadow-sm ring-1 ring-inset ring-slate-200 transition hover:bg-slate-50 hover:text-brand-700 hover:ring-slate-300">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                {{ $back['label'] }}
            </a>
        </div>
    @endif
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3 min-w-0">
            @if ($icon)
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-white text-brand-600 ring-1 ring-slate-200 shadow-sm shrink-0">
                    <x-icon :name="$icon" class="w-5 h-5" />
                </span>
            @endif
            <div class="min-w-0">
                <h1 class="text-xl sm:text-2xl font-semibold tracking-tight text-slate-900 truncate">{{ $title }}</h1>
                @if ($subtitle)<p class="mt-0.5 text-sm text-slate-500">{{ $subtitle }}</p>@endif
            </div>
        </div>
        @isset($actions)
            <div class="flex items-center gap-2 shrink-0">{{ $actions }}</div>
        @endisset
    </div>
</div>
