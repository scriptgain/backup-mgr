{{-- Self-hosted license status banner. Renders only when the license needs
     attention, and only for admins (who can act on it). Never blocks the UI. --}}
@php
    $lic = null;
    try {
        if (auth()->check() && auth()->user()->isAdmin()) {
            $lic = \App\Services\LicenseClient::status();
        }
    } catch (\Throwable $e) {
        $lic = null;
    }
@endphp
@if ($lic && $lic['state'] !== 'valid')
    @php
        $tone = match ($lic['state']) {
            'grace', 'unlicensed' => ['bg' => 'bg-amber-50', 'ring' => 'ring-amber-200', 'text' => 'text-amber-800', 'icon' => 'text-amber-500'],
            default => ['bg' => 'bg-red-50', 'ring' => 'ring-red-200', 'text' => 'text-red-800', 'icon' => 'text-red-500'],
        };
        $heading = match ($lic['state']) {
            'unlicensed' => 'License Key Required',
            'grace'      => 'License Server Unreachable',
            'invalid'    => 'License Not Valid',
            'unverified' => 'License Could Not Be Verified',
            default      => 'License Attention Needed',
        };
    @endphp
    <div {{ $attributes->merge(['class' => 'rounded-xl ' . $tone['bg'] . ' ring-1 ring-inset ' . $tone['ring'] . ' px-4 py-3']) }}>
        <div class="flex items-start gap-3">
            <x-icon name="shield" class="w-5 h-5 shrink-0 mt-0.5 {{ $tone['icon'] }}" />
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold {{ $tone['text'] }}">{{ $heading }}</p>
                <p class="text-sm {{ $tone['text'] }} opacity-90">{{ $lic['message'] }}
                    Enter or update your key with <code class="font-mono text-xs">php artisan backup:license &lt;key&gt;</code>,
                    or purchase one at <a href="https://scriptgain.com/products/backup-manager" class="underline font-medium" target="_blank" rel="noopener">scriptgain.com</a>.
                </p>
            </div>
        </div>
    </div>
@endif
