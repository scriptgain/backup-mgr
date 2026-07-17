@props(['flush' => false])
{{-- Styled table. Consumers write plain <thead>/<tbody>/<th>/<td>; cell styling
     is applied automatically. The table always fits its container (fixed layout):
     long text cells truncate with an ellipsis and get a hover tooltip, so the
     table never scrolls sideways. Plain CSS so the purged build can't strip it. --}}
<style>
    .vx-table { width: 100%; table-layout: fixed; }
    .vx-table td, .vx-table th { white-space: nowrap; }
    /* Truncate text cells to their column; leave cells holding controls alone. */
    .vx-table td:not(:has(button, form, input, select)),
    .vx-table th:not(:has(button, form, input, select)) { overflow: hidden; text-overflow: ellipsis; }
    /* Selection / narrow utility columns size to their control, not an equal share. */
    .vx-table th.w-10, .vx-table td.w-10 { width: 5rem; }
    /* Right-aligned trailing columns are almost always action buttons: give them
       enough fixed width for up to four icon buttons so nothing is clipped. */
    .vx-table th.text-right:last-child, .vx-table td.text-right:last-child { width: 13rem; }
</style>
<div class="{{ $flush ? '' : 'rounded-xl ring-1 ring-slate-200 bg-white shadow-sm overflow-hidden' }}">
    <table {{ $attributes->merge(['class' =>
        'vx-table w-full text-sm text-left tabular '
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
