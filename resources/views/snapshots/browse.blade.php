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
                viewMode: 'list',
                tree: null,
                buildTree(){
                    const root = { name:'', path:'', dir:true, kids:[], _m:{}, expanded:true };
                    for (const f of this.files) {
                        const parts = String(f.path).replace(/^\/+/,'').split('/').filter(Boolean);
                        let node = root, acc = '';
                        parts.forEach((part, idx) => {
                            acc += '/' + part;
                            const isLast = idx === parts.length - 1;
                            if (!node._m[part]) { const c = { name:part, path:acc, dir:!isLast, kids:[], _m:{}, expanded:false, size:isLast?f.size:null }; node._m[part]=c; node.kids.push(c); }
                            node = node._m[part];
                            if (!isLast) node.dir = true;
                        });
                    }
                    const sortRec = (n) => { n.kids.sort((a,b)=>(b.dir-a.dir)||a.name.localeCompare(b.name)); n.kids.forEach(sortRec); };
                    sortRec(root); this.tree = root;
                },
                setView(m){ if (m === 'tree' && !this.tree) this.buildTree(); this.viewMode = m; },
                get treeRows(){ const out=[]; const walk=(n,d)=>{ for(const k of n.kids){ out.push({node:k,depth:d}); if(k.dir && k.expanded) walk(k,d+1); } }; if(this.tree) walk(this.tree,0); return out; },
                isCovered(node){ return this.selected.some(s => node.path !== s && node.path.startsWith(s + '/')); },
                isSel(node){ return this.selected.includes(node.path) || this.isCovered(node); },
                toggleNode(node){
                    if (this.isCovered(node)) return;
                    const i = this.selected.indexOf(node.path);
                    if (i >= 0) { this.selected.splice(i,1); return; }
                    if (node.dir) { this.selected = this.selected.filter(s => s !== node.path && !s.startsWith(node.path + '/')); }
                    this.selected.push(node.path);
                },
             }"
             class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <x-card :title="'Files (' . count($run->file_index) . ')'">
                    <x-slot:actions>
                        <div class="inline-flex overflow-hidden rounded-lg text-sm ring-1 ring-inset ring-slate-300">
                            <button type="button" @click="setView('list')" :class="viewMode==='list' ? 'bg-brand-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-50'" class="px-2.5 py-1.5 font-medium">List</button>
                            <button type="button" @click="setView('tree')" :class="viewMode==='tree' ? 'bg-brand-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-50'" class="px-2.5 py-1.5 font-medium border-l border-slate-300">Tree</button>
                        </div>
                        <button type="button" x-show="viewMode==='list'" @click="toggleAll()" :class="allSelected ? 'bg-brand-50 text-brand-700 ring-brand-200' : 'bg-white text-slate-600 ring-slate-300 hover:bg-slate-50'" class="inline-flex items-center gap-2 rounded-lg ring-1 ring-inset px-2.5 py-1.5 text-sm font-medium transition">
                            <span class="relative inline-flex h-4 w-7 items-center rounded-full transition-colors shrink-0" :class="allSelected ? 'bg-brand-600' : 'bg-slate-300'">
                                <span class="inline-block h-3 w-3 rounded-full bg-white shadow transition-transform" :class="allSelected ? 'translate-x-3.5' : 'translate-x-0.5'"></span>
                            </span>
                            <span x-text="allSelected ? 'Clear' : 'All'"></span>
                        </button>
                        <input type="text" x-show="viewMode==='list'" x-model="q" placeholder="Search files…" class="rounded-lg border-0 bg-white px-3 py-1.5 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-500 w-40">
                    </x-slot:actions>
                    {{-- List view --}}
                    <div x-show="viewMode==='list'" class="vx-scroll max-h-[28rem] overflow-y-auto rounded-lg ring-1 ring-slate-200 bg-slate-50/40 p-1">
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
                    {{-- Tree view (expand/collapse; select a folder to restore it whole) --}}
                    <div x-show="viewMode==='tree'" class="vx-scroll max-h-[28rem] overflow-y-auto rounded-lg ring-1 ring-slate-200 bg-slate-50/40 p-1 text-sm">
                        <template x-for="row in treeRows" :key="row.node.path">
                            <div class="group flex items-center gap-1.5 rounded-md py-1 pr-2 transition-colors" :style="'padding-left:' + (0.4 + row.depth*1.1) + 'rem'" :class="isSel(row.node) ? 'bg-brand-50 ring-1 ring-inset ring-brand-200/70' : 'hover:bg-slate-50'">
                                <button type="button" x-show="row.node.dir" @click="row.node.expanded = !row.node.expanded" class="w-5 h-5 flex items-center justify-center rounded text-slate-400 hover:text-brand-700 hover:bg-slate-200 shrink-0">
                                    <svg class="w-3.5 h-3.5 transition-transform duration-150" :class="row.node.expanded ? 'rotate-90' : ''" viewBox="0 0 20 20" fill="currentColor"><path d="M7 5l6 5-6 5V5z"/></svg>
                                </button>
                                <span x-show="!row.node.dir" class="w-5 shrink-0"></span>
                                <button type="button" @click="toggleNode(row.node)" :disabled="isCovered(row.node)" :title="isCovered(row.node) ? 'Included by a selected parent folder' : ''" class="w-4 h-4 rounded border flex items-center justify-center shrink-0 transition-colors disabled:opacity-60" :class="isSel(row.node) ? 'bg-brand-600 border-brand-600 text-white' : 'bg-white border-slate-300 hover:border-brand-400'">
                                    <svg x-show="isSel(row.node)" class="w-3 h-3" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l3 3 7-7"/></svg>
                                </button>
                                <x-icon name="folder" class="w-4 h-4 shrink-0 text-amber-500" x-show="row.node.dir" />
                                <x-icon name="archive" class="w-4 h-4 shrink-0 text-slate-300" x-show="!row.node.dir" />
                                <span class="truncate flex-1" :class="row.node.dir ? 'font-medium text-slate-800' : 'text-slate-600'" x-text="row.node.name"></span>
                                <span class="text-xs text-slate-400 tabular shrink-0" x-text="row.node.dir ? '' : fmt(row.node.size)"></span>
                            </div>
                        </template>
                        <p x-show="treeRows.length === 0" class="px-2 py-3 text-xs text-slate-400">Nothing to show.</p>
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
