<x-layouts.app title="New Director">
    <x-page-header title="New Director" icon="cloud" subtitle="A node that executes its hosts' backups and gateways agentless connectors." />

    <form method="POST" action="{{ route('directors.store') }}" class="space-y-6">
        @csrf
        <x-card title="Identity">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <x-field label="Name" for="name" required :error="$errors->first('name')">
                    <x-input id="name" name="name" :value="old('name')" required autofocus placeholder="e.g. Phoenix Node 1" />
                </x-field>
                <x-field label="Location" for="location_id" hint="Which site this node runs in.">
                    <x-select id="location_id" name="location_id">
                        <option value="">Unassigned</option>
                        @foreach ($locations as $l)
                            <option value="{{ $l->id }}" @selected(old('location_id', $selectedLocation) == $l->id)>{{ $l->name }}</option>
                        @endforeach
                    </x-select>
                </x-field>
            </div>
        </x-card>

        <x-card title="Node Address" subtitle="Where this Director node can be reached">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <x-field label="Hostname / IP" for="hostname" hint="DNS name or IP of the node." :error="$errors->first('hostname')">
                    <x-input id="hostname" name="hostname" :value="old('hostname')" placeholder="node1.example.com or 10.0.0.5" />
                </x-field>
                <x-field label="Port" for="port" :error="$errors->first('port')">
                    <x-input id="port" name="port" type="number" :value="old('port')" placeholder="9443" />
                </x-field>
            </div>
        </x-card>

        <x-card title="Notes">
            <x-field for="notes" :error="$errors->first('notes')">
                <textarea id="notes" name="notes" rows="3"
                    class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">{{ old('notes') }}</textarea>
            </x-field>
        </x-card>

        <div class="flex items-center justify-end gap-2">
            <x-button variant="secondary" href="{{ route('directors.index') }}">Cancel</x-button>
            <x-button type="submit" icon="plus">Create Director</x-button>
        </div>
    </form>
</x-layouts.app>
