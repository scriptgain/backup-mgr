@php
    $statusMap = [
        'valid' => ['success', 'Valid'],
        'unverified' => ['warn', 'Not Yet Verified'],
        'grace' => ['warn', 'Grace Period'],
        'invalid' => ['danger', 'Invalid'],
        'unlicensed' => ['neutral', 'No Key'],
    ];
    [$statusColor, $statusLabel] = $statusMap[$license['status']] ?? ['neutral', ucfirst($license['status'] ?? 'Unknown')];
    $masked = $license['key'] ? \Illuminate\Support\Str::mask($license['key'], '*', 4, max(0, strlen($license['key']) - 8)) : null;
@endphp
<x-layouts.app title="License">
    <x-page-header title="License" icon="shield" subtitle="Manage your {{ $license['product'] }} license key and entitlement.">
        <x-slot:actions>
            <x-button variant="secondary" icon="settings" href="{{ route('settings.index') }}">Settings</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-card title="License Key">
                <form method="POST" action="{{ route('settings.license.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <x-field label="Key" for="license_key" hint="Paste the key issued by ScriptGain, for example BKM-XXXX-XXXX-XXXX. Leave blank to remove it." :error="$errors->first('license_key')">
                        <x-input id="license_key" name="license_key" :value="old('license_key', $license['key'])" placeholder="BKM-XXXX-XXXX-XXXX" autocomplete="off" />
                    </x-field>
                    <div class="flex items-center gap-2">
                        <x-button type="submit" icon="check">Save Key</x-button>
                    </div>
                </form>
            </x-card>

            <x-card title="Sync & Validation">
                <p class="text-sm text-slate-600">Re-check your entitlement and pull the latest plan and expiry. Online validation against ScriptGain (signed and verifiable offline) will be enabled soon; until then, Sync stores and applies your key locally.</p>
                <form method="POST" action="{{ route('settings.license.sync') }}" class="mt-4">
                    @csrf
                    <x-button type="submit" variant="secondary" icon="sync">Sync Now</x-button>
                </form>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card title="Status">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Status</dt><dd><x-badge :color="$statusColor" dot>{{ $statusLabel }}</x-badge></dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Product</dt><dd class="font-medium text-slate-900">{{ $license['product'] }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Plan</dt><dd class="font-medium text-slate-900">{{ $license['plan'] ?: '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Key</dt><dd class="font-mono text-xs text-slate-700">{{ $masked ?: '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Last Checked</dt><dd class="text-slate-700">{{ $license['checked_at'] ? \Illuminate\Support\Carbon::parse($license['checked_at'])->diffForHumans() : 'Never' }}</dd></div>
                </dl>
            </x-card>
            <x-card title="Need A Key?">
                <p class="text-sm text-slate-600">Purchase or manage your subscription at <a href="https://scriptgain.com/products/backup-manager" target="_blank" rel="noopener" class="text-brand-700 hover:text-brand-800 font-medium">scriptgain.com</a>.</p>
            </x-card>
        </div>
    </div>
</x-layouts.app>
