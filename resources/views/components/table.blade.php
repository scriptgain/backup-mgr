@props(['flush' => false])
{{-- Styled table. Consumers write plain <thead>/<tbody>/<th>/<td>; cell styling
     is applied automatically. Scrolls horizontally on small screens. Use
     `flush` when placed inside an <x-card flush> so it has no double border. --}}
<div class="{{ $flush ? 'overflow-x-auto' : 'overflow-x-auto rounded-xl ring-1 ring-slate-200 bg-white shadow-sm' }}">
    <table {{ $attributes->merge(['class' =>
        'min-w-full text-sm text-left tabular '
        . '[&_thead]:bg-slate-50 [&_thead_th]:px-4 [&_thead_th]:py-3 [&_thead_th]:font-medium [&_thead_th]:text-xs [&_thead_th]:uppercase [&_thead_th]:tracking-wide [&_thead_th]:text-slate-500 '
        . '[&_tbody_tr]:border-t [&_tbody_tr]:border-slate-100 [&_tbody_tr:hover]:bg-slate-50/60 '
        . '[&_tbody_td]:px-4 [&_tbody_td]:py-3 [&_tbody_td]:text-slate-700 [&_tbody_td]:align-middle']) }}>
        {{ $slot }}
    </table>
</div>
