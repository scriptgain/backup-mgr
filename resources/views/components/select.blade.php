<div class="relative">
    <select {{ $attributes->merge(['class' =>
        'block w-full appearance-none rounded-lg border-0 bg-white pl-3 pr-11 py-2 text-sm text-slate-900 '
        . 'ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500 disabled:opacity-60']) }}>
        {{ $slot }}
    </select>
    <x-icon name="chevron-down" class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
</div>
