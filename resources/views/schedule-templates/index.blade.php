<x-layouts.app title="Schedule Templates">
    <x-page-header title="Schedule Templates" icon="clock" subtitle="Prebuilt schedules you can assign to hosts and jobs.">
        <x-slot:actions>
            <x-button variant="secondary" icon="settings" href="{{ route('settings.index') }}">Settings</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <x-card title="Templates" flush>
                <x-table flush>
                    <thead><tr><th>Name</th><th>Cron</th><th>Description</th><th class="text-right">Actions</th></tr></thead>
                    <tbody>
                        @foreach ($templates as $t)
                            <tr>
                                <td class="font-medium text-slate-900">{{ $t->name }} @if ($t->is_system)<x-badge color="info" class="ml-1">System</x-badge>@endif</td>
                                <td class="font-mono text-xs tabular">{{ $t->cron }}</td>
                                <td class="text-slate-500">{{ $t->description }}</td>
                                <td class="text-right">
                                    @unless ($t->is_system)
                                        <x-delete-button :name="'del-tmpl-' . $t->id" :action="route('schedule-templates.destroy', $t)"
                                            title="Delete Template?" message="Hosts using it as a default will fall back to none." />
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
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
