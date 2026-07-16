<x-layouts.app title="Edit Location">
    <x-page-header title="Edit Location" icon="folder" :subtitle="$location->name" />

    <x-card>
        <form method="POST" action="{{ route('locations.update', $location) }}" class="space-y-5">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <x-field label="Name" for="name" required :error="$errors->first('name')">
                    <x-input id="name" name="name" :value="old('name', $location->name)" required />
                </x-field>
                <x-field label="Region" for="region" :error="$errors->first('region')">
                    <x-input id="region" name="region" :value="old('region', $location->region)" />
                </x-field>
            </div>
            <x-field label="Address" for="address" :error="$errors->first('address')">
                <x-input id="address" name="address" :value="old('address', $location->address)" />
            </x-field>
            <x-field label="Notes" for="notes">
                <textarea id="notes" name="notes" rows="3"
                    class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">{{ old('notes', $location->notes) }}</textarea>
            </x-field>
            <div class="flex items-center justify-end gap-2 pt-1">
                <x-button variant="secondary" href="{{ route('locations.show', $location) }}">Cancel</x-button>
                <x-button type="submit" icon="check">Save Changes</x-button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
