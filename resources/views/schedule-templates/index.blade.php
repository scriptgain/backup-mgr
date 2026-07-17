<x-layouts.app title="Schedule Templates">
    <x-page-header title="Schedule Templates" icon="clock" subtitle="Prebuilt schedules you can assign to hosts and jobs.">
        <x-slot:actions>
            <x-button variant="secondary" icon="settings" href="{{ route('settings.index') }}">Settings</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <x-card title="Templates" flush>
                <div x-data="{ selected: [], confirming: false, allIds: [{{ $templates->pluck('id')->implode(',') }}], submitBulk() { const f = this.$refs.bulkForm; f.querySelectorAll('input.js-dyn').forEach(n => n.remove()); this.selected.forEach(id => { const i = document.createElement('input'); i.type='hidden'; i.name='ids[]'; i.value=id; i.className='js-dyn'; f.appendChild(i); }); f.submit(); } }">
                    <form method="POST" action="{{ route('schedule-templates.bulk-destroy') }}" x-ref="bulkForm" class="hidden">@csrf @method('DELETE')</form>
                    <div x-show="selected.length" x-cloak class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-brand-50 px-4 py-2.5">
                        <span class="text-sm font-medium text-brand-800"><span x-text="selected.length"></span> selected</span>
                        <div class="flex items-center gap-2">
                            <template x-if="! confirming"><x-button type="button" variant="danger" size="sm" icon="trash" x-on:click="confirming = true">Delete Selected</x-button></template>
                            <template x-if="confirming">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm text-brand-800">Delete <span x-text="selected.length"></span> template(s)?</span>
                                    <x-button type="button" variant="secondary" size="sm" x-on:click="confirming = false">Cancel</x-button>
                                    <x-button type="button" variant="danger" size="sm" icon="trash" x-on:click="submitBulk()">Confirm Delete</x-button>
                                </div>
                            </template>
                        </div>
                    </div>
                    <x-table flush>
                        <thead><tr><th class="w-10">@include('jobs._select-all-toggle')</th><th>Name</th><th>Cron</th><th>Description</th><th class="text-right">Actions</th></tr></thead>
                        <tbody>
                            @foreach ($templates as $t)
                                <tr>
                                    <td>@include('jobs._select-toggle', ['id' => $t->id])</td>
                                    <td class="font-medium text-slate-900">{{ $t->name }} @if ($t->is_system)<x-badge color="info" class="ml-1">System</x-badge>@endif</td>
                                    <td class="font-mono text-xs tabular">{{ $t->cron }}</td>
                                    <td class="text-slate-500">{{ $t->description }}</td>
                                    <td class="text-right">
                                        <x-delete-button :name="'del-tmpl-' . $t->id" :action="route('schedule-templates.destroy', $t)"
                                            title="Delete Template?" message="Hosts using it as a default will fall back to none." />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-table>
                </div>
            </x-card>
        </div>

        <div>
            <x-card title="Add Template">
                <form method="POST" action="{{ route('schedule-templates.store') }}" class="space-y-4">
                    @csrf
                    <x-field label="Name" for="name" required :error="$errors->first('name')">
                        <x-input id="name" name="name" :value="old('name')" placeholder="e.g. Twice Daily" />
                    </x-field>
                    <x-field label="Cron" for="cron" required hint="Standard 5-field cron." :error="$errors->first('cron')">
                        <x-input id="cron" name="cron" :value="old('cron')" placeholder="0 */12 * * *" />
                    </x-field>
                    <x-field label="Description" for="description" :error="$errors->first('description')">
                        <x-input id="description" name="description" :value="old('description')" />
                    </x-field>
                    <x-button type="submit" icon="plus" class="w-full">Add Template</x-button>
                </form>
            </x-card>
        </div>
    </div>
</x-layouts.app>
