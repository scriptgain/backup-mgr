@php $hasIndex = is_array($run->file_index) && count($run->file_index); @endphp
<x-layouts.app :title="'Browse Snapshot'">
    <x-page-header title="Browse Snapshot" icon="folder"
        :subtitle="($run->job?->name ?? 'Job') . ' · ' . ($run->job?->host?->name ?? '')">
        <x-slot:actions>
            <x-button variant="secondary" icon="archive" href="{{ route('snapshots.index') }}">Snapshots</x-button>
        </x-slot:actions>
    </x-page-header>

    @if (! $hasIndex)
        <x-card>
            <x-empty-state icon="folder" title="No File Listing" description="This snapshot was taken before file browsing existed, or the listing wasn't uploaded. Run the job again to browse its files, or use Restore to recover the whole snapshot." />
        </x-card>
    @else
        <div x-data="{
                q: '',
                target: '{{ ($run->job?->source['root'] ?? '') ?: '/var/restore' }}',
                selected: [],
                files: {{ \Illuminate\Support\Js::from($run->file_index) }},
                fmt(b){ if(b==null) return ''; const u=['B','KB','MB','GB']; let i=0; while(b>=1024&&i<3){b/=1024;i++;} return (i? b.toFixed(1):b)+' '+u[i]; },
                get filtered(){ const q=this.q.toLowerCase(); return (q ? this.files.filter(f=>f.path.toLowerCase().includes(q)) : this.files); },
                toggle(p){ const i=this.selected.indexOf(p); i>=0 ? this.selected.splice(i,1) : this.selected.push(p); },
                get allSelected(){ const f=this.filtered; return f.length>0 && f.every(x=>this.selected.includes(x.path)); },
                toggleAll(){ const paths=this.filtered.map(x=>x.path); if(this.allSelected){ this.selected=this.selected.filter(p=>!paths.includes(p)); } else { this.selected=[...new Set([...this.selected, ...paths])]; } },
             }"
             class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <x-card :title="'Files (' . count($run->file_index) . ')'">
                    <x-slot:actions>
                        <button type="button" @click="toggleAll()" :class="allSelected ? 'bg-brand-50 text-brand-700 ring-brand-200' : 'bg-white text-slate-600 ring-slate-300 hover:bg-slate-50'" class="inline-flex items-center gap-2 rounded-lg ring-1 ring-inset px-2.5 py-1.5 text-sm font-medium transition">
                            <span class="relative inline-flex h-4 w-7 items-center rounded-full transition-colors shrink-0" :class="allSelected ? 'bg-brand-600' : 'bg-slate-300'">
                                <span class="inline-block h-3 w-3 rounded-full bg-white shadow transition-transform" :class="allSelected ? 'translate-x-3.5' : 'translate-x-0.5'"></span>
                            </span>
                            <span x-text="allSelected ? 'Clear' : 'All'"></span>
                        </button>
                        <input type="text" x-model="q" placeholder="Search files…" class="rounded-lg border-0 bg-white px-3 py-1.5 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-500 w-40">
                    </x-slot:actions>
                    <div class="max-h-[28rem] overflow-y-auto -mx-1">
                        <template x-for="f in filtered.slice(0, 1000)" :key="f.path">
                            <div @click="toggle(f.path)" :class="selected.includes(f.path) ? 'bg-brand-50 ring-1 ring-inset ring-brand-200' : 'ring-1 ring-inset ring-transparent hover:bg-slate-50 hover:ring-slate-200'" class="flex items-center gap-3 px-2 py-1.5 rounded-lg cursor-pointer select-none">
                                <button type="button" role="switch" :aria-checked="selected.includes(f.path).toString()"
                                    :class="selected.includes(f.path) ? 'bg-brand-600' : 'bg-slate-300'"
                                    class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors pointer-events-none">
                                    <span :class="selected.includes(f.path) ? 'translate-x-4' : 'translate-x-0.5'" class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform"></span>
                                </button>
                                <x-icon name="folder" class="w-4 h-4 shrink-0 text-slate-400" x-show="f.dir" />
                                <x-icon name="archive" class="w-4 h-4 shrink-0 text-slate-300" x-show="!f.dir" />
                                <span class="font-mono text-xs text-slate-700 truncate flex-1" x-text="f.path"></span>
                                <span class="text-xs text-slate-400 tabular shrink-0" x-text="f.dir ? '' : fmt(f.size)"></span>
                            </div>
                        </template>
                        <p class="px-2 py-3 text-xs text-slate-400" x-show="filtered.length > 1000">Showing first 1000 of <span x-text="filtered.length"></span>. Refine your search.</p>
                        <p class="px-2 py-3 text-sm text-slate-400" x-show="filtered.length === 0">No files match.</p>
                    </div>
                </x-card>
            </div>

            <div>
                <x-card title="Restore">
                    <form method="POST" action="{{ route('restores.store') }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="run_id" value="{{ $run->id }}">
                        <template x-for="p in selected" :key="p"><input type="hidden" name="paths[]" :value="p"></template>
                        <p class="text-sm text-slate-600"><span class="font-semibold" x-text="selected.length"></span> file(s) selected. Leave none to restore the whole snapshot.</p>
                        <x-field label="Restore To Path" for="target_path" hint="On the host.">
                            <x-input id="target_path" name="target_path" x-model="target" required />
                        </x-field>
                        <x-button type="submit" variant="primary" icon="restore" class="w-full" x-text="selected.length ? 'Restore Selected' : 'Restore Whole Snapshot'">Restore</x-button>
                    </form>
                </x-card>
            </div>
        </div>
    @endif
</x-layouts.app>
