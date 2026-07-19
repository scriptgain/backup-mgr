@props(['title', 'subtitle' => null, 'icon' => null, 'back' => null])
{{-- Slim page header (house style). Title + optional subtitle on the left, actions slot on the right. --}}
<div {{ $attributes->merge(['class' => 'pb-5']) }}>
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
