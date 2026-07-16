<x-layouts.app title="Branding">
    <x-page-header title="Branding" icon="edit" subtitle="Rename and re-color the whole product.">
        <x-slot:actions>
            <x-button variant="secondary" icon="settings" href="{{ route('settings.index') }}">Settings</x-button>
        </x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ route('settings.branding.update') }}"
          x-data="{ accent: '{{ config('brand.accent') }}', name: '{{ addslashes(config('brand.name')) }}' }" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <x-card title="Identity">
                    <div class="space-y-5">
                        <x-field label="Product Name" for="brand_name" required :error="$errors->first('brand_name')">
                            <x-input id="brand_name" name="brand_name" x-model="name" :value="config('brand.name')" required />
                        </x-field>
                        <x-field label="Tagline" for="brand_tagline" :error="$errors->first('brand_tagline')">
                            <x-input id="brand_tagline" name="brand_tagline" :value="config('brand.tagline')" />
                        </x-field>
                        <x-field label="Accent Color" for="brand_accent" hint="Hex, e.g. #06b6d4." :error="$errors->first('brand_accent')">
                            <div class="flex items-center gap-3">
                                <input type="color" x-model="accent" class="h-10 w-14 rounded-lg border border-slate-300 bg-white p-1">
                                <x-input id="brand_accent" name="brand_accent" x-model="accent" class="font-mono" />
                            </div>
                        </x-field>
                    </div>
                </x-card>
            </div>

            <div>
                <x-card title="Preview">
                    <div class="rounded-xl overflow-hidden ring-1 ring-slate-200">
                        <div class="px-4 py-3 text-white flex items-center gap-2" :style="`background:#0b1220`">
                            <x-icon name="shield" class="w-5 h-5" x-bind:style="`color:${accent}`" />
                            <span class="font-semibold" x-text="name || 'BackupMGR'"></span>
                        </div>
                        <div class="p-4 bg-white space-y-3">
                            <button type="button" class="w-full rounded-lg text-white text-sm font-medium py-2" x-bind:style="`background:${accent}`">Primary Button</button>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                  x-bind:style="`background:${accent}1a;color:${accent}`" x-text="'Accent Badge'"></span>
                        </div>
                    </div>
                </x-card>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2">
            <x-button variant="secondary" href="{{ route('settings.index') }}">Cancel</x-button>
            <x-button type="submit" icon="check">Save Branding</x-button>
        </div>
    </form>
</x-layouts.app>
