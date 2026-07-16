@php
    $g = fn ($k) => $c[$k] ?? '';
@endphp
<x-layouts.app title="Host & SSL">
    <x-page-header title="Host & SSL" icon="globe" subtitle="Set this install's hostname and issue an SSL certificate."
        :back="['href' => route('settings.index'), 'label' => 'Settings']" />

    @if (session('warning'))
        <div class="mb-6"><x-alert type="warn">{{ session('warning') }}</x-alert></div>
    @endif

    <x-alert type="info" class="mb-6">
        This install is currently reached at
        <span class="font-mono font-medium">{{ $currentUrl }}</span>@if ($serverIp) (server IP <span class="font-mono">{{ $serverIp }}</span>)@endif.
        Point a domain's DNS at this server, set the hostname below, then issue a certificate.
    </x-alert>

    {{-- Current certificate status --}}
    <x-card title="Current Certificate" class="mb-6">
        @if ($status)
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-slate-500">Common Name</dt>
                    <dd class="font-medium text-slate-900 font-mono truncate">{{ $status['subject'] ?: '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-slate-500">Issuer</dt>
                    <dd class="font-medium text-slate-900 truncate">{{ $status['issuer'] ?: '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-slate-500">Expires</dt>
                    <dd class="font-medium text-slate-900">{{ $status['expires_at'] ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-slate-500">Status</dt>
                    <dd>
                        @if ($status['expired'])
                            <x-badge color="danger">Expired</x-badge>
                        @elseif ($status['self_signed'])
                            <x-badge color="warn">Self-Signed</x-badge>
                        @elseif (($status['days_left'] ?? 99) <= 14)
                            <x-badge color="warn">Expires In {{ $status['days_left'] }} Days</x-badge>
                        @else
                            <x-badge color="success">Valid · {{ $status['days_left'] }} Days Left</x-badge>
                        @endif
                    </dd>
                </div>
            </dl>
        @else
            <p class="text-sm text-slate-500">No certificate found at the configured path yet.</p>
        @endif
    </x-card>

    {{-- Hostname + paths --}}
    <form method="POST" action="{{ route('settings.host.update') }}" class="space-y-6">
        @csrf
        @method('PUT')
        <x-card title="Hostname" subtitle="The domain this install should serve.">
            <div class="space-y-5">
                <x-field label="Hostname" for="app_hostname" hint="For example app.example.com. No http:// and no trailing slash." :error="$errors->first('app_hostname')">
                    <x-input id="app_hostname" name="app_hostname" :value="$g('app_hostname')" placeholder="app.example.com" />
                </x-field>
                <x-field label="Let's Encrypt Contact Email" for="ssl_le_email" hint="Used to register with the certificate authority and receive expiry notices." :error="$errors->first('ssl_le_email')">
                    <x-input id="ssl_le_email" name="ssl_le_email" type="email" :value="$g('ssl_le_email')" placeholder="admin@example.com" />
                </x-field>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <x-field label="Certificate Path" for="ssl_cert_path" hint="Where the fullchain cert is written.">
                        <x-input id="ssl_cert_path" name="ssl_cert_path" class="font-mono text-xs" :value="$g('ssl_cert_path')" />
                    </x-field>
                    <x-field label="Private Key Path" for="ssl_key_path" hint="Where the private key is written.">
                        <x-input id="ssl_key_path" name="ssl_key_path" class="font-mono text-xs" :value="$g('ssl_key_path')" />
                    </x-field>
                    <x-field label="ACME Webroot" for="ssl_webroot" hint="Public dir served at the hostname for HTTP-01 checks.">
                        <x-input id="ssl_webroot" name="ssl_webroot" class="font-mono text-xs" :value="$g('ssl_webroot')" />
                    </x-field>
                    <x-field label="Reload Command" for="ssl_reload_cmd" hint="Run after a cert changes, e.g. sudo systemctl reload nginx. Leave blank if not applicable.">
                        <x-input id="ssl_reload_cmd" name="ssl_reload_cmd" class="font-mono text-xs" :value="$g('ssl_reload_cmd')" placeholder="sudo systemctl reload nginx" />
                    </x-field>
                </div>
            </div>
            <x-slot:footer>
                <div class="flex items-center justify-end gap-2">
                    <x-button type="submit" icon="check">Save Host Settings</x-button>
                </div>
            </x-slot:footer>
        </x-card>
    </form>

    {{-- Externally-managed certificate: a valid, trusted cert is already served
         for this host and we have no record of issuing it. Hide the issuance /
         overwrite controls and show a read-only notice instead. --}}
    @if ($unmanagedDetected ?? false)
    <x-card title="SSL Certificate" class="mt-6">
        <div class="flex items-start gap-3">
            <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100 shrink-0">
                <x-icon name="shield-check" class="w-5 h-5" />
            </span>
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="text-[15px] font-semibold text-slate-900">Unmanaged by us, detected</h3>
                    <x-badge color="neutral">External</x-badge>
                </div>
                <p class="mt-1 text-sm text-slate-500">
                    A valid TLS certificate is already being served for
                    <span class="font-mono">{{ $currentHost }}</span>@if (($serving['issuer'] ?? '') !== ''), issued by
                    <span class="font-medium text-slate-700">{{ $serving['issuer'] }}</span>@endif@if (! empty($serving['expires_at'])) and valid until
                    <span class="font-medium text-slate-700">{{ $serving['expires_at'] }}</span>@endif.
                    It was not issued through this panel, so certificate management is disabled here to avoid overwriting it.
                    Manage this certificate wherever it was set up (for example your host or web-server panel).
                </p>
            </div>
        </div>
    </x-card>
    @else
    {{-- Issuance methods --}}
    <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Let's Encrypt --}}
        <x-card title="Let's Encrypt" subtitle="Automatic, trusted, free.">
            <p class="text-sm text-slate-500">
                Issues and installs a browser-trusted certificate over HTTP-01. The hostname's DNS must resolve to this server and port 80 must be reachable.
            </p>
            @if (! $acme)
                <div class="mt-3"><x-alert type="warn" title="acme.sh Not Installed">
                    Install it once over SSH as the site user, then return here:
                    <code class="mt-1 block font-mono text-xs">curl https://get.acme.sh | sh</code>
                </x-alert></div>
            @endif
            <form method="POST" action="{{ route('settings.host.letsencrypt') }}" class="mt-4">@csrf
                <x-button type="submit" icon="shield-check" class="w-full" :disabled="! $acme">Issue / Renew Certificate</x-button>
            </form>
        </x-card>

        {{-- Upload --}}
        <x-card title="Upload Certificate" subtitle="Bring your own cert and key.">
            <form method="POST" action="{{ route('settings.host.upload') }}" class="space-y-4">@csrf
                <x-field label="Certificate (Fullchain PEM)" for="certificate" :error="$errors->first('certificate')">
                    <textarea id="certificate" name="certificate" rows="4" placeholder="-----BEGIN CERTIFICATE-----"
                        class="block w-full rounded-lg border-0 bg-white px-3 py-2 font-mono text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500"></textarea>
                </x-field>
                <x-field label="Private Key (PEM)" for="private_key" :error="$errors->first('private_key')">
                    <textarea id="private_key" name="private_key" rows="4" placeholder="-----BEGIN PRIVATE KEY-----" autocomplete="off"
                        class="block w-full rounded-lg border-0 bg-white px-3 py-2 font-mono text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500"></textarea>
                </x-field>
                <x-button type="submit" variant="secondary" icon="check" class="w-full">Install Certificate</x-button>
            </form>
        </x-card>

        {{-- Self-signed --}}
        <x-card title="Self-Signed" subtitle="Last resort, not trusted.">
            <p class="text-sm text-slate-500">
                Generates a self-signed certificate for the hostname. Browsers will warn on it, so use this only until a real certificate is in place.
            </p>
            <form method="POST" action="{{ route('settings.host.self-signed') }}" class="mt-4">@csrf
                <x-button type="submit" variant="secondary" icon="lock" class="w-full">Generate Self-Signed</x-button>
            </form>
        </x-card>
    </div>
    @endif

    {{-- Last run log --}}
    @if ($g('ssl_last_output'))
        <x-card title="Last Result" :subtitle="$g('ssl_last_action') ? ucfirst($g('ssl_last_action')).' · '.$g('ssl_last_at') : null" class="mt-6">
            <pre class="whitespace-pre-wrap break-words rounded-lg bg-slate-900 p-4 font-mono text-xs text-slate-100 overflow-x-auto">{{ $g('ssl_last_output') }}</pre>
        </x-card>
    @endif
</x-layouts.app>
