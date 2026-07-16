@php
    $tone = [
        'created' => 'success',
        'deleted' => 'danger',
        'updated' => 'info',
        'login' => 'info',
        'logout' => 'neutral',
        'backup' => 'info',
        'restore' => 'warn',
    ];
@endphp
<x-layouts.app title="Audit Log">
    <x-page-header title="Audit Log" icon="book" subtitle="Who signed in and changed what, across the fleet.">
        <x-slot:actions>
            <x-button variant="secondary" icon="settings" href="{{ route('settings.index') }}">Settings</x-button>
        </x-slot:actions>
    </x-page-header>


    <div x-data="{
            selected: [],
            pageIds: {{ \Illuminate\Support\Js::from($logs->pluck('id')->values()) }},
            toggle(id) { const i = this.selected.indexOf(id); i >= 0 ? this.selected.splice(i, 1) : this.selected.push(id); },
            get allOn() { return this.pageIds.length > 0 && this.pageIds.every(i => this.selected.includes(i)); },
            toggleAll() { if (this.allOn) { this.selected = this.selected.filter(i => !this.pageIds.includes(i)); } else { this.selected = [...new Set([...this.selected, ...this.pageIds])]; } },
        }">

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            @if ($actions->isNotEmpty())
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('settings.audit.index') }}"
                       class="px-3 py-1.5 rounded-lg text-sm ring-1 transition {{ ! $action ? 'bg-brand-50 text-brand-700 ring-brand-200' : 'bg-white text-slate-600 ring-slate-200 hover:ring-brand-300' }}">All</a>
                    @foreach ($actions as $a)
                        <a href="{{ route('settings.audit.index', ['action' => $a]) }}"
                           class="px-3 py-1.5 rounded-lg text-sm ring-1 transition {{ $action === $a ? 'bg-brand-50 text-brand-700 ring-brand-200' : 'bg-white text-slate-600 ring-slate-200 hover:ring-brand-300' }}">{{ ucfirst($a) }}</a>
                    @endforeach
                </div>
            @else
                <div></div>
            @endif

            <div class="flex items-center gap-2">
                <span x-show="selected.length" x-cloak class="text-sm text-slate-500"><span x-text="selected.length"></span> selected</span>
                <x-button variant="danger" size="sm" icon="trash" x-show="selected.length" x-cloak
                    x-on:click="$dispatch('open-modal', 'audit-del-selected')">Delete Selected</x-button>
                @if ($logs->total())
                    <x-button variant="secondary" size="sm" icon="trash"
                        x-on:click="$dispatch('open-modal', 'audit-del-all')">Delete All</x-button>
                @endif
            </div>
        </div>

        <x-card flush>
            @if ($logs->isEmpty())
                <x-empty-state icon="book" title="No Activity Yet" description="Sign-ins and changes to directors, hosts, jobs, and users will appear here." />
            @else
                <x-table flush>
                    <thead>
                        <tr>
                            <th class="w-10">
                                <button type="button" @click="toggleAll()" role="switch" :aria-checked="allOn.toString()" title="Select all on this page"
                                    :class="allOn ? 'bg-brand-600' : 'bg-slate-300'"
                                    class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors align-middle">
                                    <span :class="allOn ? 'translate-x-4' : 'translate-x-0.5'" class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform"></span>
                                </button>
                            </th>
                            <th>Action</th><th>Detail</th><th>User</th><th>IP</th><th class="text-right">When</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr :class="selected.includes({{ $log->id }}) ? 'bg-brand-50/50' : ''">
                                <td>
                                    <button type="button" @click="toggle({{ $log->id }})" role="switch" :aria-checked="selected.includes({{ $log->id }}).toString()"
                                        :class="selected.includes({{ $log->id }}) ? 'bg-brand-600' : 'bg-slate-300'"
                                        class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors align-middle">
                                        <span :class="selected.includes({{ $log->id }}) ? 'translate-x-4' : 'translate-x-0.5'" class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform"></span>
                                    </button>
                                </td>
                                <td><x-badge :color="$tone[$log->action] ?? 'neutral'">{{ ucfirst($log->action) }}</x-badge></td>
                                <td class="text-slate-700">{{ $log->description }}</td>
                                <td class="text-slate-500">{{ $log->user?->name ?? 'System' }}</td>
                                <td class="text-slate-400 tabular">{{ $log->ip ?? '-' }}</td>
                                <td class="text-right text-slate-500" title="{{ $log->created_at }}">{{ $log->created_at?->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </x-card>

        @if ($logs->hasPages())
            <div class="mt-4">{{ $logs->links() }}</div>
        @endif

        {{-- Confirm modals live inside this x-data so they can read `selected`. --}}
        <x-modal name="audit-del-selected" title="Delete Selected Entries?" icon="warning" tone="danger" maxWidth="max-w-md">
            Delete <span x-text="selected.length"></span> selected audit <span x-text="selected.length === 1 ? 'entry' : 'entries'"></span>? This cannot be undone.
            <x-slot:footer>
                <x-button variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'audit-del-selected')">Cancel</x-button>
                <form method="POST" action="{{ route('settings.audit.destroy-selected') }}">
                    @csrf @method('DELETE')
                    <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                    <x-button variant="danger" size="sm" type="submit" icon="trash">Delete</x-button>
                </form>
            </x-slot:footer>
        </x-modal>

        <x-modal name="audit-del-all" title="Clear Entire Audit Log?" icon="warning" tone="danger" maxWidth="max-w-md">
            This permanently deletes <span class="font-medium">all {{ $logs->total() }}</span> audit entries across every page. This cannot be undone.
            <x-slot:footer>
                <x-button variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'audit-del-all')">Cancel</x-button>
                <form method="POST" action="{{ route('settings.audit.destroy-all') }}">
                    @csrf @method('DELETE')
                    <x-button variant="danger" size="sm" type="submit" icon="trash">Delete All</x-button>
                </form>
            </x-slot:footer>
        </x-modal>
    </div>
</x-layouts.app>
