@php
    $editing = $folder->exists;
    $hostDisks = $hosts->mapWithKeys(fn ($h) => [$h->id => array_values($h->disks ?? [])]);
    $initialTargets = old('targets', $folder->targets ?: [['host_id' => '', 'path' => '']]);
@endphp
<x-layouts.app :title="$editing ? 'Edit Sync Folder' : 'New Sync Folder'">
    <x-page-header :title="$editing ? 'Edit Sync Folder' : 'New Sync Folder'" icon="sync"
        subtitle="Choose one main folder, then the hosts to keep in sync with it." />

    @if ($errors->any())
        <div class="mb-6"><x-alert type="danger" title="Please fix the highlighted fields">Check the main source, targets, and schedule below.</x-alert></div>
    @endif

    <form method="POST" action="{{ $editing ? route('settings.sync.update', $folder) : route('settings.sync.store') }}"
          x-data="{
              sourceId: '{{ old('source_host_id', $folder->source_host_id) }}',
              hostDisks: {{ \Illuminate\Support\Js::from($hostDisks) }},
              targets: {{ \Illuminate\Support\Js::from($initialTargets) }},
              addTarget() { this.targets.push({ host_id: '', path: '' }); },
              removeTarget(i) { this.targets.splice(i, 1); },
          }">
        @csrf
        @if ($editing) @method('PUT') @endif

        <x-card>
            <x-field label="Sync Name" for="name" required :error="$errors->first('name')">
                <x-input id="name" name="name" :value="old('name', $folder->name)" placeholder="Web roots mirror" />
            </x-field>

            <div class="my-6 border-t border-slate-100"></div>

            {{-- Main source --}}
            <div class="flex items-center gap-2 mb-3">
                <x-icon name="server" class="w-4 h-4 text-brand-600" />
                <h3 class="text-sm font-semibold text-slate-900">Main Source</h3>
                <span class="text-xs text-slate-400">The folder everything else follows</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <x-field label="Main Host" for="source_host_id" required :error="$errors->first('source_host_id')">
                    <x-select id="source_host_id" name="source_host_id" x-model="sourceId">
                        <option value="">Select a host…</option>
                        @foreach ($hosts as $h)
                            <option value="{{ $h->id }}" @selected(old('source_host_id', $folder->source_host_id) == $h->id)>{{ $h->name }} — {{ $h->director?->name }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-field label="Main Folder Path" for="source_path" required :error="$errors->first('source_path')">
                    <input id="source_path" name="source_path" value="{{ old('source_path', $folder->source_path) }}" list="source-disks"
                        placeholder="/var/www/site" class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm font-mono text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                    <datalist id="source-disks"><template x-for="d in (hostDisks[sourceId] || [])" :key="d"><option :value="d"></option></template></datalist>
                </x-field>
            </div>

            <div class="my-6 border-t border-slate-100"></div>

            {{-- Targets --}}
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <x-icon name="sync" class="w-4 h-4 text-brand-600" />
                    <h3 class="text-sm font-semibold text-slate-900">Kept In Sync</h3>
                    <span class="text-xs text-slate-400">These hosts receive a copy of the main folder</span>
                </div>
            </div>
            <div class="space-y-3">
                <template x-for="(t, i) in targets" :key="i">
                    <div class="flex flex-wrap items-end gap-3 rounded-lg bg-slate-50 ring-1 ring-inset ring-slate-200 p-3">
                        <div class="flex-1 min-w-[12rem]">
                            <label class="block text-xs font-medium text-slate-600 mb-1">Target Host</label>
                            <select :name="`targets[${i}][host_id]`" x-model="t.host_id"
                                class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                                <option value="">Select a host…</option>
                                @foreach ($hosts as $h)
                                    <option value="{{ $h->id }}">{{ $h->name }} — {{ $h->director?->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-1 min-w-[12rem]">
                            <label class="block text-xs font-medium text-slate-600 mb-1">Target Path</label>
                            <input :name="`targets[${i}][path]`" x-model="t.path" placeholder="/var/www/site"
                                class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm font-mono text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                        </div>
                        <button type="button" @click="removeTarget(i)" x-show="targets.length > 1"
                            class="mb-0.5 text-slate-400 hover:text-rose-600 p-2 shrink-0" title="Remove target">
                            <x-icon name="trash" class="w-4 h-4" />
                        </button>
                    </div>
                </template>
                @error('targets') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                @foreach ($errors->get('targets.*') as $msgs) <p class="text-sm text-rose-600">{{ $msgs[0] }}</p> @endforeach
                <button type="button" @click="addTarget()"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-700 hover:text-brand-800">
                    <x-icon name="plus" class="w-4 h-4" /> Add Another Host
                </button>
            </div>

            <div class="my-6 border-t border-slate-100"></div>

            {{-- Schedule + options --}}
            <div class="flex items-center gap-2 mb-3">
                <x-icon name="clock" class="w-4 h-4 text-brand-600" />
                <h3 class="text-sm font-semibold text-slate-900">Schedule</h3>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 items-start">
                <x-field label="Sync Every (minutes)" for="interval_minutes" required :error="$errors->first('interval_minutes')">
                    <x-input id="interval_minutes" name="interval_minutes" type="number" min="1" max="10080"
                        :value="old('interval_minutes', $folder->interval_minutes ?: 15)" />
                </x-field>
                <div class="space-y-4 pt-1">
                    <x-toggle name="delete_extra" :checked="old('delete_extra', $folder->delete_extra)"
                        label="Mirror Mode" description="Delete files on targets that no longer exist on the main." />
                    <x-toggle name="enabled" :checked="old('enabled', $folder->exists ? $folder->enabled : true)"
                        label="Enabled" description="Turn syncing on for this folder." />
                </div>
            </div>
        </x-card>

        <div class="mt-6 flex items-center justify-end gap-3">
            <x-button variant="secondary" href="{{ route('settings.sync.index') }}">Cancel</x-button>
            <x-button variant="primary" type="submit" icon="sync">{{ $editing ? 'Save Sync Folder' : 'Create Sync Folder' }}</x-button>
        </div>
    </form>
</x-layouts.app>
