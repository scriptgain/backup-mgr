@props(['host', 'mode' => 'view', 'label' => 'Browse Files'])
@if (config('backup.demo'))
    {{-- File managers are always disabled in the public demo. --}}
    <button type="button" disabled title="File browsing is disabled in the demo" {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 text-sm font-medium text-slate-300 cursor-not-allowed']) }}>
        <x-icon name="folder" class="w-4 h-4" /> {{ $label }}
    </button>
@else
{{-- Live file browser for a host, over its own connection (empty path = login
     directory). mode="pick" adds a chosen folder to a form via a window event
     ('file-picked'); mode="view" is read-only. Directory listing only. --}}
<div x-data="{
        open: false,
        path: '',
        parent: null,
        entries: [],
        error: '',
        loading: false,
        truncated: false,
        async load(p) {
            this.loading = true; this.error = '';
            try {
                const res = await fetch('{{ route('hosts.browse', $host) }}?path=' + encodeURIComponent(p ?? ''), { headers: { 'Accept': 'application/json' } });
                if (!res.ok) throw new Error('http ' + res.status);
                const d = await res.json();
                this.path = d.path; this.parent = d.parent; this.entries = d.entries || [];
                this.truncated = !!d.truncated; this.error = d.error || '';
            } catch (e) { this.entries = []; this.error = 'Could not reach this host.'; }
            this.loading = false;
        },
        openNow() { this.open = true; this.load(this.path || ''); },
        pick(p) { $dispatch('file-picked', p); this.open = false; },
     }" class="inline-flex">
    <button type="button" @click="openNow()" {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-brand-700']) }}>
        <x-icon name="folder" class="w-4 h-4" /> {{ $label }}
    </button>

    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="background-color: rgba(15,23,42,.55)" @keydown.escape.window="open = false">
        <div class="w-full max-w-2xl bg-white rounded-xl shadow-2xl ring-1 ring-slate-200 flex flex-col text-left"
            style="max-height: 80vh" @click.outside="open = false">
            <div class="flex items-center justify-between px-5 py-3 border-b border-slate-100">
                <h3 class="text-xl font-semibold text-slate-900 flex items-center gap-2"><x-icon name="folder" class="w-5 h-5 text-brand-600" /> Files on {{ $host->name }}</h3>
                <button type="button" @click="open = false" class="text-slate-400 hover:text-slate-600"><x-icon name="x" class="w-5 h-5" /></button>
            </div>
            <div class="flex items-center gap-2 px-5 py-2.5 border-b border-slate-100 bg-slate-50">
                <button type="button" @click="load('')" title="Login directory" class="p-1.5 rounded-md text-slate-500 hover:bg-white hover:text-brand-700"><x-icon name="home" class="w-4 h-4" /></button>
                <button type="button" @click="parent !== null && load(parent)" :disabled="parent === null" title="Up one level" class="p-1.5 rounded-md text-slate-500 hover:bg-white hover:text-brand-700 disabled:opacity-40"><x-icon name="arrow-up" class="w-4 h-4" /></button>
                <code class="flex-1 min-w-0 truncate text-xs text-slate-600" x-text="path || '(login directory)'"></code>
                @if ($mode === 'pick')
                    <button type="button" @click="pick(path)" class="shrink-0 text-xs font-semibold text-brand-700 hover:text-brand-800">Use This Folder</button>
                @endif
            </div>
            <div class="vx-wrap flex-1 overflow-y-auto overflow-x-hidden">
                <div x-show="loading" class="p-8 text-center text-sm text-slate-400">Loading&hellip;</div>
                <div x-show="!loading && error" class="p-8 text-center text-sm text-rose-600" x-text="error"></div>
                <ul x-show="!loading && !error" class="divide-y divide-slate-50">
                    <template x-for="e in entries" :key="e.path">
                        <li class="flex items-center gap-3 px-5 py-2 hover:bg-slate-50">
                            <button type="button" x-show="e.is_dir" @click="load(e.path)" class="flex items-center gap-2 flex-1 min-w-0 text-left">
                                <x-icon name="folder" class="w-4 h-4 text-amber-500 shrink-0" />
                                <span class="text-sm text-slate-700 truncate" x-text="e.name"></span>
                            </button>
                            <span x-show="!e.is_dir" class="flex items-center gap-2 flex-1 min-w-0 text-slate-400">
                                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                                <span class="text-sm truncate" x-text="e.name"></span>
                            </span>
                            @if ($mode === 'pick')
                                <button type="button" x-show="e.is_dir" @click="pick(e.path)" class="shrink-0 text-xs font-medium text-brand-700 hover:text-brand-800">Add</button>
                            @endif
                        </li>
                    </template>
                    <li x-show="entries.length === 0" class="p-8 text-center text-sm text-slate-400">This folder is empty.</li>
                </ul>
                <div x-show="truncated" class="px-5 py-2 text-xs text-amber-600 border-t border-slate-100">Showing the first 2000 entries only.</div>
            </div>
        </div>
    </div>
</div>
@endif
