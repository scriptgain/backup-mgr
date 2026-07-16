@props(['type' => 'text'])
<input type="{{ $type }}"
    {{ $attributes->merge(['class' =>
        'block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-slate-900 '
        . 'ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 '
        . 'focus:ring-2 focus:ring-inset focus:ring-brand-500 disabled:opacity-60 disabled:bg-slate-50']) }}>
