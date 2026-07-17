<x-layouts.app title="Advanced Restore">
    <x-page-header title="Advanced Restore" icon="restore"
        :subtitle="'Snapshot ' . \Illuminate\Support\Str::limit($run->snapshot_id, 16) . ' · ' . ($run->job?->name ?? 'Job')">
        <x-slot:actions>
            <x-button variant="secondary" icon="chevron-left" href="{{ route('runs.show', $run) }}">Back to Run</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($errors->any())
        <div class="mb-6"><x-alert type="danger" title="Please fix the highlighted fields">Check the destination and options below.</x-alert></div>
    @endif

    <form method="POST" action="{{ route('restores.store') }}"
          x-data="{
              hostId: '{{ old('host_id', $run->job?->host_id) }}',
              targetPath: {{ \Illuminate\Support\Js::from(old('target_path', $origin)) }},
              brOpen: false, brPath: '', brParent: null, brEntries: [], brError: '', brLoading: false, brTruncated: false,
              browseBase(){ return '{{ url('/hosts') }}/' + this.hostId + '/browse'; },
              async loadTarget(p){
                  this.brLoading = true; this.brError = '';
                  try {
                      const res = await fetch(this.browseBase() + '?path=' + encodeURIComponent(p ?? ''), { headers: { 'Accept': 'application/json' } });
                      if (!res.ok) throw new Error('http ' + res.status);
                      const d = await res.json();
                      this.brPath = d.path; this.brParent = d.parent; this.brEntries = d.entries || [];
                      this.brTruncated = !!d.truncated; this.brError = d.error || '';
                  } catch (e) { this.brEntries = []; this.brError = 'Could not reach this host over its connection.'; }
                  this.brLoading = false;
              },
              openTargetBrowser(){ this.brOpen = true; this.brPath = ''; this.newFolderOpen = false; this.newFolderName = ''; this.loadTarget(''); },
              useFolder(p){ this.targetPath = p || '/'; this.brOpen = false; },
              newFolderOpen: false,
              newFolderName: '',
              async createFolder(){
                  const name = this.newFolderName.trim();
                  if (!name) return;
                  this.brError = '';
                  try {
                      const res = await fetch('{{ url('/hosts') }}/' + this.hostId + '/mkdir', {
                          method: 'POST',
                          headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                          body: JSON.stringify({ path: this.brPath, name }),
                      });
                      const d = await res.json();
                      if (d.error) { this.brError = d.error; return; }
                      this.newFolderName = ''; this.newFolderOpen = false;
                      await this.loadTarget(this.brPath);
                  } catch (e) { this.brError = 'Could not create the folder.'; }
              },
              q: '',
              selected: [],
              files: {{ $hasIndex ? \Illuminate\Support\Js::from($run->file_index) : '[]' }},
              fmt(b){ if(b==null) return ''; const u=['B','KB','MB','GB']; let i=0; while(b>=1024&&i<3){b/=1024;i++;} return (i? b.toFixed(1):b)+' '+u[i]; },
              get filtered(){ const q=this.q.toLowerCase(); return (q ? this.files.filter(f=>f.path.toLowerCase().includes(q)) : this.files); },
              toggle(p){ const i=this.selected.indexOf(p); i>=0 ? this.selected.splice(i,1) : this.selected.push(p); },
              get allSelected(){ const f=this.filtered; return f.length>0 && f.every(x=>this.selected.includes(x.path)); },
              toggleAll(){ const paths=this.filtered.map(x=>x.path); if(this.allSelected){ this.selected=this.selected.filter(p=>!paths.includes(p)); } else { this.selected=[...new Set([...this.selected, ...paths])]; } },
              // Tree view — a real file browser built from the flat listing, with
              // + / - expand/collapse. Selecting a folder selects that folder path
              // (one recursive restore); files under it show as covered.
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
        @csrf
        <input type="hidden" name="run_id" value="{{ $run->id }}">
        <input type="hidden" name="advanced" value="1">
        <template x-for="p in selected" :key="p"><input type="hidden" name="paths[]" :value="p"></template>

        <div class="lg:col-span-2 space-y-6">
            <x-card title="Destination">
                <div class="space-y-5">
                    <x-field label="Restore To Host" for="host_id" hint="Defaults to the original host. Pick another host in the same Director to redirect the restore.">
                        <x-select id="host_id" name="host_id" x-model="hostId">
                            @foreach ($hosts as $h)
                                <option value="{{ $h->id }}" @selected(old('host_id', $run->job?->host_id) == $h->id)>{{ $h->name }}{{ $h->id === $run->job?->host_id ? ' (original)' : '' }}</option>
                            @endforeach
                        </x-select>
                    </x-field>

                    <x-field label="Target Path" for="target_path" hint="Full path (starts with /). Prefilled with the original location, or Browse to pick a folder you have access to on the target host." :error="$errors->first('target_path')">
                        <div class="flex items-center gap-2">
                            <input type="text" id="target_path" name="target_path" x-model="targetPath" placeholder="/var/restore" required
                                class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                            <button type="button" @click="openTargetBrowser()" class="shrink-0 inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-2 text-sm font-medium text-slate-600 ring-1 ring-inset ring-slate-300 hover:bg-slate-50"><x-icon name="folder" class="w-4 h-4" /> Browse</button>
                        </div>
                    </x-field>

                    {{-- Live target-folder picker: browses the SELECTED host over its own
                         connection (FTP/local now), so you pick a folder you actually have
                         access to instead of guessing. --}}
                    <div x-show="brOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
                        style="background-color: rgba(15,23,42,.55)" @keydown.escape.window="brOpen = false">
                        <div class="w-full max-w-2xl bg-white rounded-xl shadow-2xl ring-1 ring-slate-200 flex flex-col text-left"
                            style="max-height: 80vh" @click.outside="brOpen = false">
                            <div class="flex items-center justify-between px-5 py-3 border-b border-slate-100">
                                <h3 class="text-sm font-semibold text-slate-900 flex items-center gap-2"><x-icon name="folder" class="w-4 h-4 text-brand-600" /> Choose Restore Folder</h3>
                                <button type="button" @click="brOpen = false" class="text-slate-400 hover:text-slate-600"><x-icon name="x" class="w-5 h-5" /></button>
                            </div>
                            <div class="flex items-center gap-2 px-5 py-2.5 border-b border-slate-100 bg-slate-50">
                                <button type="button" @click="loadTarget('')" title="Login directory" class="p-1.5 rounded-md text-slate-500 hover:bg-white hover:text-brand-700"><x-icon name="home" class="w-4 h-4" /></button>
                                <button type="button" @click="brParent !== null && loadTarget(brParent)" :disabled="brParent === null" title="Up one level" class="p-1.5 rounded-md text-slate-500 hover:bg-white hover:text-brand-700 disabled:opacity-40"><x-icon name="arrow-up" class="w-4 h-4" /></button>
                                <code class="flex-1 min-w-0 truncate text-xs text-slate-600" x-text="brPath || '(login directory)'"></code>
                                <button type="button" @click="newFolderOpen = !newFolderOpen; $nextTick(() => newFolderOpen && $refs.newFolderInput?.focus())" class="shrink-0 inline-flex items-center gap-1 text-xs font-medium text-slate-600 hover:text-brand-700"><x-icon name="plus" class="w-3.5 h-3.5" /> New Folder</button>
                                <button type="button" @click="useFolder(brPath)" class="shrink-0 text-xs font-semibold text-brand-700 hover:text-brand-800">Use This Folder</button>
                            </div>
                            <div x-show="newFolderOpen" x-cloak class="flex items-center gap-2 px-5 py-2.5 border-b border-slate-100">
                                <input x-ref="newFolderInput" type="text" x-model="newFolderName" placeholder="New folder name" @keydown.enter.prevent="createFolder()"
                                    class="flex-1 rounded-lg border-0 bg-white px-3 py-1.5 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                                <button type="button" @click="createFolder()" class="shrink-0 rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-700">Create</button>
                                <button type="button" @click="newFolderOpen = false; newFolderName = ''" class="shrink-0 text-xs text-slate-500 hover:text-slate-700">Cancel</button>
                            </div>
                            <div class="flex-1 overflow-y-auto">
                                <div x-show="brLoading" class="p-8 text-center text-sm text-slate-400">Loading&hellip;</div>
                                <div x-show="!brLoading && brError" class="p-8 text-center text-sm text-rose-600" x-text="brError"></div>
                                <ul x-show="!brLoading && !brError" class="divide-y divide-slate-50">
                                    <template x-for="e in brEntries" :key="e.path">
                                        <li class="flex items-center gap-3 px-5 py-2 hover:bg-slate-50">
                                            <button type="button" x-show="e.is_dir" @click="loadTarget(e.path)" class="flex items-center gap-2 flex-1 min-w-0 text-left">
                                                <x-icon name="folder" class="w-4 h-4 text-amber-500 shrink-0" />
                                                <span class="text-sm text-slate-700 truncate" x-text="e.name"></span>
                                            </button>
                                            <span x-show="!e.is_dir" class="flex items-center gap-2 flex-1 min-w-0 text-slate-400">
                                                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                                                <span class="text-sm truncate" x-text="e.name"></span>
                                            </span>
                                            <button type="button" x-show="e.is_dir" @click="useFolder(e.path)" class="shrink-0 text-xs font-medium text-brand-700 hover:text-brand-800">Select</button>
                                        </li>
                                    </template>
                                    <li x-show="brEntries.length === 0" class="p-8 text-center text-sm text-slate-400">This folder is empty.</li>
                                </ul>
                                <div x-show="brTruncated" class="px-5 py-2 text-xs text-amber-600 border-t border-slate-100">Showing the first 2000 entries only.</div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-1">
                        <x-toggle name="strip_paths" :checked="(bool) old('strip_paths')" label="Strip Original Paths"
                            description="Restore file contents directly into the target folder, instead of recreating the full original path under it." />
                    </div>
                </div>
            </x-card>

            @if ($hasIndex)
                <x-card :title="'Select Files (' . count($run->file_index) . ')'">
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
                    {{-- List view (flat, searchable) --}}
                    <div x-show="viewMode==='list'" class="max-h-[26rem] overflow-y-auto -mx-1">
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
                    </div>
                    {{-- Tree view (expand/collapse folders; select a folder to restore it whole) --}}
                    <div x-show="viewMode==='tree'" class="max-h-[26rem] overflow-y-auto -mx-1 text-sm">
                        <template x-for="row in treeRows" :key="row.node.path">
                            <div class="flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-slate-50" :style="'padding-left:' + (0.5 + row.depth*1.15) + 'rem'" :class="isSel(row.node) ? 'bg-brand-50/60' : ''">
                                <button type="button" x-show="row.node.dir" @click="row.node.expanded = !row.node.expanded" class="w-4 h-4 flex items-center justify-center rounded font-mono text-xs leading-none text-slate-400 hover:bg-slate-200 hover:text-brand-700 shrink-0"><span x-text="row.node.expanded ? '−' : '+'"></span></button>
                                <span x-show="!row.node.dir" class="w-4 shrink-0"></span>
                                <button type="button" @click="toggleNode(row.node)" :disabled="isCovered(row.node)" role="switch" :aria-checked="isSel(row.node).toString()" :class="isSel(row.node) ? 'bg-brand-600' : 'bg-slate-300'" class="relative inline-flex h-4 w-7 shrink-0 items-center rounded-full transition-colors disabled:opacity-50">
                                    <span :class="isSel(row.node) ? 'translate-x-3.5' : 'translate-x-0.5'" class="inline-block h-3 w-3 rounded-full bg-white shadow transition-transform"></span>
                                </button>
                                <x-icon name="folder" class="w-4 h-4 shrink-0 text-amber-500" x-show="row.node.dir" />
                                <x-icon name="archive" class="w-4 h-4 shrink-0 text-slate-300" x-show="!row.node.dir" />
                                <span class="truncate flex-1" :class="row.node.dir ? 'font-medium text-slate-700' : 'text-slate-600'" x-text="row.node.name"></span>
                                <span class="text-xs text-slate-400 tabular shrink-0" x-text="row.node.dir ? '' : fmt(row.node.size)"></span>
                            </div>
                        </template>
                        <p x-show="treeRows.length === 0" class="px-2 py-3 text-xs text-slate-400">Nothing to show.</p>
                    </div>
                    <p class="mt-3 text-xs text-slate-500"><span class="font-semibold" x-text="selected.length"></span> item(s) selected. Selecting a folder restores it and everything inside. Leave none to restore the whole snapshot.</p>
                </x-card>
            @else
                <x-card title="Files">
                    <x-empty-state icon="folder" title="Whole Snapshot" description="This snapshot has no file listing, so the entire snapshot will be restored to the target." />
                </x-card>
            @endif
        </div>

        <div class="space-y-6">
            <x-card title="Conflict Policy">
                <x-field label="When A File Already Exists" for="overwrite">
                    <x-select id="overwrite" name="overwrite">
                        <option value="overwrite" @selected(old('overwrite') === 'overwrite')>Overwrite existing</option>
                        <option value="skip" @selected(old('overwrite') === 'skip')>Skip existing (never overwrite)</option>
                        <option value="keep_newer" @selected(old('overwrite') === 'keep_newer')>Keep newer (only overwrite if the backup is newer)</option>
                    </x-select>
                </x-field>
            </x-card>

            <x-card title="Attributes">
                <div class="space-y-4">
                    <x-toggle name="restore_ownership" :checked="(bool) old('restore_ownership', 1)" label="Restore Ownership" description="Reapply original user/group." />
                    <x-toggle name="restore_permissions" :checked="(bool) old('restore_permissions', 1)" label="Restore Permissions" description="Reapply original file modes." />
                </div>
            </x-card>

            <x-card title="Mode">
                <x-toggle name="dry_run" :checked="(bool) old('dry_run')" label="Dry Run" description="Verify only. Report what would be restored without writing anything." />
            </x-card>

            <x-button type="submit" variant="primary" icon="restore" class="w-full">Queue Restore</x-button>
        </div>
    </form>
</x-layouts.app>
