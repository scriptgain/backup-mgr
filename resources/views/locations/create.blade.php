<x-layouts.app title="New Location">
    <x-page-header title="New Location" icon="folder" subtitle="A site or region that holds one or more Director nodes." />

    <x-card>
        <form method="POST" action="{{ route('locations.store') }}" class="space-y-5">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <x-field label="Name" for="name" required :error="$errors->first('name')">
                    <x-input id="name" name="name" :value="old('name')" required autofocus placeholder="e.g. Phoenix DC" />
                </x-field>
                <x-field label="Region" for="region" :error="$errors->first('region')">
                    <x-input id="region" name="region" :value="old('region')" placeholder="e.g. US-West" />
                </x-field>
            </div>
            <x-field label="Address" for="address" hint="Optional street address or datacenter identifier." :error="$errors->first('address')">
                <x-input id="address" name="address" :value="old('address')" placeholder="e.g. 123 Main St, Phoenix, AZ" />
            </x-field>
            <x-field label="Notes" for="notes" :error="$errors->first('notes')">
                <textarea id="notes" name="notes" rows="3"
                    class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">{{ old('notes') }}</textarea>
            </x-field>
            <div class="flex items-center justify-end gap-2 pt-1">
                <x-button variant="secondary" href="{{ route('locations.index') }}">Cancel</x-button>
                <x-button type="submit" icon="plus">Create Location</x-button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
