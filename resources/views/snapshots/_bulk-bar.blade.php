{{-- Bulk delete bar for the Snapshots tables. Lives inside an Alpine scope
     exposing `selected`, `confirming` and `submitBulk()`, and posts to the
     endpoint that deletes the snapshots themselves, not the run records. --}}
<form method="POST" action="{{ route('snapshots.bulk-destroy') }}" x-ref="bulkForm" class="hidden">@csrf @method('DELETE')</form>
<div x-show="selected.length" x-cloak class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-brand-50 px-4 py-2.5">
    <span class="text-sm font-medium text-brand-800"><span x-text="selected.length"></span> selected</span>
    <div class="flex items-center gap-2">
        <template x-if="! confirming">
            <x-button type="button" variant="danger" size="sm" icon="trash" x-on:click="confirming = true">Delete Snapshots</x-button>
        </template>
        <template x-if="confirming">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm text-brand-800">Delete <span x-text="selected.length"></span> snapshot(s) from their repositories? The backup data is removed and cannot be restored.</span>
                <x-button type="button" variant="secondary" size="sm" x-on:click="confirming = false">Cancel</x-button>
                <x-button type="button" variant="danger" size="sm" icon="trash" x-on:click="submitBulk()">Confirm Delete</x-button>
            </div>
        </template>
    </div>
</div>
