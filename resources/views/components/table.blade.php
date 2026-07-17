@props(['flush' => false])
{{-- Styled table. Consumers write plain <thead>/<tbody>/<th>/<td>; cell styling
     is applied automatically. Cells never wrap; long text cells truncate with an
     ellipsis and get a hover tooltip with the full value. Scrolls horizontally if
     the table is wider than its container. Plain CSS so the purged build can't strip it. --}}
<style>
    .vx-table td, .vx-table th { white-space: nowrap; }
    .vx-table td:not(:has(button, form, input, select)) { max-width: 24rem; overflow: hidden; text-overflow: ellipsis; }
</style>
<div class="{{ $flush ? 'overflow-x-auto' : 'overflow-x-auto rounded-xl ring-1 ring-slate-200 bg-white shadow-sm' }}">
    <table {{ $attributes->merge(['class' =>
        'vx-table min-w-full text-sm text-left tabular '
        . '[&_thead]:bg-slate-50 [&_thead_th]:px-4 [&_thead_th]:py-3 [&_thead_th]:font-medium [&_thead_th]:text-xs [&_thead_th]:uppercase [&_thead_th]:tracking-wide [&_thead_th]:text-slate-500 '
        . '[&_tbody_tr]:border-t [&_tbody_tr]:border-slate-100 [&_tbody_tr:hover]:bg-slate-50/60 '
        . '[&_tbody_td]:px-4 [&_tbody_td]:py-3 [&_tbody_td]:text-slate-700 [&_tbody_td]:align-middle']) }}>
        {{ $slot }}
    </table>
</div>
<script>
    // Native tooltip on any truncated table cell (full text on hover).
    (function () {
        function tag() {
            document.querySelectorAll('.vx-table td').forEach(function (td) {
                if (!td.title && td.scrollWidth > td.clientWidth + 1) td.title = td.textContent.trim();
            });
        }
        if (document.readyState !== 'loading') tag();
        else document.addEventListener('DOMContentLoaded', tag);
    })();
</script>
