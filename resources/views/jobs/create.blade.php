@php $hostDisks = $hosts->mapWithKeys(fn ($h) => [$h->id => array_values($h->disks ?? [])]); @endphp
<x-layouts.app title="New Backup Job">
    <x-page-header title="New Backup Job" icon="clock" subtitle="Define what to protect, where it goes, and how long to keep it." />

    @if ($hosts->isEmpty() || $repositories->isEmpty())
        <x-alert type="warn" title="A Couple Things First">
            <ul class="mt-1 space-y-1 list-disc list-inside">
                @if ($hosts->isEmpty())<li>No hosts yet — <a href="{{ route('directors.index') }}" class="underline font-medium">add a host</a> under a Director.</li>@endif
                @if ($repositories->isEmpty())<li>No repositories yet — <a href="{{ route('repositories.create') }}" class="underline font-medium">create a repository</a>.</li>@endif
            </ul>
        </x-alert>
    @endif

    <form method="POST" action="{{ route('jobs.store') }}"
          x-data="{ tab: 'target', type: '{{ old('type', 'files') }}', hostId: '{{ old('host_id', $selectedHost) }}', hostDisks: {{ \Illuminate\Support\Js::from($hostDisks) }}, excludes: {{ \Illuminate\Support\Js::from(old('excludes', [])) }}, cpaths: {{ \Illuminate\Support\Js::from(old('composite_paths', [['label' => '', 'root' => '']])) }}, cdbs: {{ \Illuminate\Support\Js::from(old('composite_dbs', [])) }}, cexcludes: {{ \Illuminate\Support\Js::from(old('composite_excludes', [])) }}, devices: {{ \Illuminate\Support\Js::from(old('devices', [''])) }} }" class="mt-2">
        @csrf
        @if ($errors->any())
            <div class="mb-6"><x-alert type="danger" title="Please fix the highlighted fields">Some fields need attention. Check each tab.</x-alert></div>
        @endif

        <x-card>
            <nav class="flex flex-wrap items-center gap-1 pb-4 mb-5 border-b border-slate-100">
                @foreach ([['target', 'Target', 'server'], ['source', 'What To Back Up', 'folder'], ['schedule', 'Schedule & Retention', 'clock']] as [$key, $label, $ico])
                    <button type="button" @click="tab='{{ $key }}'" :class="tab==='{{ $key }}' ? 'bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200' : 'text-slate-500 hover:bg-slate-100'" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium transition"><x-icon :name="$ico" class="w-4 h-4" />{{ $label }}</button>
                @endforeach
            </nav>

            {{-- Target --}}
            <div x-show="tab==='target'" class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <x-field label="Host" for="host_id" required :error="$errors->first('host_id')">
                    <x-select id="host_id" name="host_id" x-model="hostId">
                        <option value="">Select a host…</option>
                        @foreach ($hosts as $h)<option value="{{ $h->id }}" @selected(old('host_id', $selectedHost) == $h->id)>{{ $h->name }} — {{ $h->director?->name }}</option>@endforeach
                    </x-select>
                </x-field>
                <x-field label="Repository" for="repository_id" required :error="$errors->first('repository_id')">
                    <x-select id="repository_id" name="repository_id">
                        <option value="">Select a repository…</option>
                        @foreach ($repositories as $r)<option value="{{ $r->id }}" @selected(old('repository_id') == $r->id)>{{ $r->name }} ({{ $r->backend }})</option>@endforeach
                    </x-select>
                </x-field>
                <x-field label="Job Name" for="name" required :error="$errors->first('name')">
                    <x-input id="name" name="name" :value="old('name')" placeholder="e.g. Site Roots" />
                </x-field>
                <x-field label="Type" for="type" required>
                    <x-select id="type" name="type" x-model="type">
                        <option value="files">Files &amp; Directories</option>
                        <option value="mysql">MySQL / MariaDB</option>
                        <option value="postgres">PostgreSQL</option>
                        <option value="composite">Whole Server (Files + Databases)</option>
                        <option value="diskimage">Disk Image (Bare Metal)</option>
                    </x-select>
                </x-field>
            </div>

            {{-- Source --}}
            <div x-show="tab==='source'" x-cloak>
                <div x-show="type === 'files'">
                    <x-field label="Path" for="path" hint="Absolute path to snapshot. Leave blank on an agentless host for the whole account." :error="$errors->first('path')">
                        <input id="path" name="path" value="{{ old('path') }}" list="host-disks" x-ref="pathInput"
                            class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500" placeholder="/var/www">
                        <datalist id="host-disks"><template x-for="d in (hostDisks[hostId] || [])" :key="d"><option :value="d"></option></template></datalist>
                    </x-field>
                    <div class="mt-3 flex flex-wrap items-center gap-2" x-show="(hostDisks[hostId] || []).length">
                        <template x-for="d in (hostDisks[hostId] || [])" :key="d">
                            <button type="button" @click="$refs.pathInput.value = d" class="inline-flex rounded-lg bg-slate-100 hover:bg-brand-50 hover:text-brand-700 px-2.5 py-1 text-xs font-mono transition"><span x-text="d"></span></button>
                        </template>
                    </div>
                    <div class="mt-4">
                        <button type="button" @click="$refs.pathInput.value='/'; excludes=['/proc','/sys','/dev','/run','/tmp','/var/backups','/mnt','/media','/lost+found']"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200 hover:bg-brand-100 px-3 py-1.5 text-sm font-medium transition">
                            <x-icon name="database" class="w-4 h-4" /> Full System Preset
                        </button>
                        <span class="ml-2 text-xs text-slate-400">Sets path to / and excludes system dirs.</span>
                    </div>
                    <div class="mt-5">
                        <p class="text-sm font-medium text-slate-700 mb-2">Exclude Paths</p>
                        <div class="space-y-2">
                            <template x-for="(ex, i) in excludes" :key="i">
                                <div class="flex items-center gap-2">
                                    <input type="text" name="excludes[]" x-model="excludes[i]" class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm font-mono text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500" placeholder="/proc">
                                    <button type="button" @click="excludes.splice(i, 1)" class="text-slate-400 hover:text-rose-600 p-2 shrink-0"><x-icon name="x" class="w-4 h-4" /></button>
                                </div>
                            </template>
                            <button type="button" @click="excludes.push('')" class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-700 hover:text-brand-800"><x-icon name="plus" class="w-4 h-4" /> Add Exclude</button>
                        </div>
                    </div>
                </div>
                <div x-show="type === 'mysql' || type === 'postgres'" x-cloak class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <x-field label="Database" for="database"><x-input id="database" name="database" :value="old('database')" /></x-field>
                    <x-field label="DB User" for="db_user"><x-input id="db_user" name="db_user" :value="old('db_user')" autocomplete="off" /></x-field>
                    <x-field label="DB Password" for="db_password"><x-input id="db_password" name="db_password" type="password" autocomplete="new-password" /></x-field>
                </div>

                {{-- Composite: one or more paths plus one or more databases, captured in a single snapshot. --}}
                <div x-show="type === 'composite'" x-cloak class="space-y-6">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-medium text-slate-700">File Paths</p>
                            <span class="text-xs text-slate-400">Each becomes a top-level folder in the snapshot</span>
                        </div>
                        <div class="space-y-2">
                            <template x-for="(p, i) in cpaths" :key="i">
                                <div class="flex items-center gap-2">
                                    <input type="text" :name="`composite_paths[${i}][label]`" x-model="p.label" placeholder="label (e.g. web)" class="w-40 shrink-0 rounded-lg border-0 bg-white px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                                    <input type="text" :name="`composite_paths[${i}][root]`" x-model="p.root" placeholder="/var/www" class="flex-1 rounded-lg border-0 bg-white px-3 py-2 text-sm font-mono text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                                    <button type="button" @click="cpaths.splice(i, 1)" class="text-slate-400 hover:text-rose-600 p-2 shrink-0"><x-icon name="x" class="w-4 h-4" /></button>
                                </div>
                            </template>
                            <button type="button" @click="cpaths.push({label: '', root: ''})" class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-700 hover:text-brand-800"><x-icon name="plus" class="w-4 h-4" /> Add Path</button>
                        </div>
                    </div>

                    <div class="border-t border-slate-100 pt-5">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-medium text-slate-700">Databases</p>
                            <span class="text-xs text-slate-400">Dumped and stored alongside the files</span>
                        </div>
                        <div class="space-y-2">
                            <template x-for="(d, i) in cdbs" :key="i">
                                <div class="grid grid-cols-12 gap-2 items-center">
                                    <input type="text" :name="`composite_dbs[${i}][label]`" x-model="d.label" placeholder="label" class="col-span-2 rounded-lg border-0 bg-white px-2.5 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                                    <select :name="`composite_dbs[${i}][engine]`" x-model="d.engine" class="col-span-2 rounded-lg border-0 bg-white px-2.5 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                                        <option value="mysql">MySQL</option>
                                        <option value="postgres">Postgres</option>
                                    </select>
                                    <input type="text" :name="`composite_dbs[${i}][database]`" x-model="d.database" placeholder="db name" class="col-span-3 rounded-lg border-0 bg-white px-2.5 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                                    <input type="text" :name="`composite_dbs[${i}][user]`" x-model="d.user" placeholder="user" autocomplete="off" class="col-span-2 rounded-lg border-0 bg-white px-2.5 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                                    <input type="password" :name="`composite_dbs[${i}][password]`" x-model="d.password" placeholder="password" autocomplete="new-password" class="col-span-2 rounded-lg border-0 bg-white px-2.5 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                                    <button type="button" @click="cdbs.splice(i, 1)" class="col-span-1 text-slate-400 hover:text-rose-600 p-2 justify-self-center"><x-icon name="x" class="w-4 h-4" /></button>
                                </div>
                            </template>
                            <button type="button" @click="cdbs.push({label: '', engine: 'mysql', database: '', user: '', password: ''})" class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-700 hover:text-brand-800"><x-icon name="plus" class="w-4 h-4" /> Add Database</button>
                        </div>
                    </div>

                    <div class="border-t border-slate-100 pt-5">
                        <p class="text-sm font-medium text-slate-700 mb-2">Exclude Patterns</p>
                        <div class="space-y-2">
                            <template x-for="(ex, i) in cexcludes" :key="i">
                                <div class="flex items-center gap-2">
                                    <input type="text" name="composite_excludes[]" x-model="cexcludes[i]" class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm font-mono text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500" placeholder="**/node_modules">
                                    <button type="button" @click="cexcludes.splice(i, 1)" class="text-slate-400 hover:text-rose-600 p-2 shrink-0"><x-icon name="x" class="w-4 h-4" /></button>
                                </div>
                            </template>
                            <button type="button" @click="cexcludes.push('')" class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-700 hover:text-brand-800"><x-icon name="plus" class="w-4 h-4" /> Add Exclude</button>
                        </div>
                    </div>

                    <p class="text-xs text-slate-400">Whole-server jobs run on the host's own agent and are stored as a single restore point (files under <span class="font-mono">files-*</span>, databases under <span class="font-mono">db-*</span>).</p>
                </div>

                {{-- Disk image: raw block-device images for bare-metal recovery. --}}
                <div x-show="type === 'diskimage'" x-cloak class="space-y-4">
                    <x-alert type="warn" title="Bare-Metal Disk Imaging">
                        Reads whole block devices with <span class="font-mono">dd</span> into raw images, deduped by kopia so nightly images stay cheap. The agent must run as <span class="font-medium">root</span>. Restores land the <span class="font-mono">.img</span> file; writing an image back onto a device is a deliberate manual step.
                    </x-alert>
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-medium text-slate-700">Block Devices</p>
                            <span class="text-xs text-slate-400">e.g. /dev/sda or /dev/sda1</span>
                        </div>
                        <div class="space-y-2">
                            <template x-for="(dev, i) in devices" :key="i">
                                <div class="flex items-center gap-2">
                                    <input type="text" name="devices[]" x-model="devices[i]" list="host-disks-img" class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm font-mono text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500" placeholder="/dev/sda">
                                    <button type="button" @click="devices.splice(i, 1)" class="text-slate-400 hover:text-rose-600 p-2 shrink-0"><x-icon name="x" class="w-4 h-4" /></button>
                                </div>
                            </template>
                            <datalist id="host-disks-img"><template x-for="d in (hostDisks[hostId] || [])" :key="d"><option :value="d"></option></template></datalist>
                            <button type="button" @click="devices.push('')" class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-700 hover:text-brand-800"><x-icon name="plus" class="w-4 h-4" /> Add Device</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Schedule & Retention --}}
            <div x-show="tab==='schedule'" x-cloak x-data="{ cron: '{{ old('schedule_cron') }}' }">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <x-field label="Template" for="tmpl" hint="Pick a prebuilt schedule, or set a custom cron.">
                        <x-select id="tmpl" x-on:change="if ($event.target.value) cron = $event.target.value">
                            <option value="">Manual only</option>
                            @foreach ($scheduleTemplates as $st)<option value="{{ $st->cron }}">{{ $st->name }} ({{ $st->cron }})</option>@endforeach
                        </x-select>
                    </x-field>
                    <x-field label="Cron Expression" for="schedule_cron" hint="Blank = manual runs only.">
                        <x-input id="schedule_cron" name="schedule_cron" x-model="cron" placeholder="0 2 * * *" />
                    </x-field>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-5 mt-5">
                    <x-field label="Keep Latest" for="keep_latest"><x-input id="keep_latest" name="keep_latest" type="number" :value="old('keep_latest', config('backup.default_keep_latest', 0))" min="0" /></x-field>
                    <x-field label="Keep Daily" for="keep_daily"><x-input id="keep_daily" name="keep_daily" type="number" :value="old('keep_daily', 7)" min="0" /></x-field>
                    <x-field label="Keep Weekly" for="keep_weekly"><x-input id="keep_weekly" name="keep_weekly" type="number" :value="old('keep_weekly', 4)" min="0" /></x-field>
                    <x-field label="Keep Monthly" for="keep_monthly"><x-input id="keep_monthly" name="keep_monthly" type="number" :value="old('keep_monthly', 6)" min="0" /></x-field>
                </div>
                <div class="mt-5 space-y-4 border-t border-slate-100 pt-5">
                    <x-toggle name="prune_after_backup" :checked="config('backup.prune_after_backup', true)" label="Prune After Each Backup" description="Apply retention and run kopia maintenance after each run." />
                    <x-toggle name="enabled" :checked="true" label="Enabled" description="Run this job on its schedule." />
                </div>
            </div>

            <x-slot:footer>
                <div class="flex items-center justify-end gap-2">
                    <x-button variant="secondary" href="{{ route('jobs.index') }}">Cancel</x-button>
                    <x-button type="submit" icon="plus">Create Job</x-button>
                </div>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.app>
