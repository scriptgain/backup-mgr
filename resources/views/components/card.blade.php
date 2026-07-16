@props(['title' => null, 'subtitle' => null, 'padding' => 'p-5 sm:p-6', 'flush' => false])
{{-- Reusable surface. Optional header (title/subtitle + actions slot), body,
     and footer slot. `flush` removes body padding and clips content to the
     rounded corners — use it when the body is a full-bleed table. --}}
@php $body = $flush ? '' : $padding; @endphp
<div {{ $attributes->merge(['class' => 'bg-white rounded-xl ring-1 ring-slate-200 shadow-sm' . ($flush ? ' overflow-hidden' : '')]) }}>
    @if ($title || isset($actions))
        <div class="flex items-start justify-between gap-4 px-5 sm:px-6 py-4 border-b border-slate-100">
            <div class="min-w-0">
                @if ($title)<h3 class="text-[15px] font-semibold text-slate-900">{{ $title }}</h3>@endif
                @if ($subtitle)<p class="mt-0.5 text-sm text-slate-500">{{ $subtitle }}</p>@endif
            </div>
            @isset($actions)
                <div class="flex items-center gap-2 shrink-0">{{ $actions }}</div>
            @endisset
        </div>
    @endif
    <div class="{{ $body }}">
        {{ $slot }}
    </div>
    @isset($footer)
        <div class="px-5 sm:px-6 py-3 border-t border-slate-100 bg-slate-50/60 rounded-b-xl">
            {{ $footer }}
        </div>
    @endisset
</div>
