<x-layouts.app title="Two-Factor Auth">
    <x-page-header title="Two-Factor Authentication" icon="shield" subtitle="A time-based code from an authenticator app, in addition to your password.">
        <x-slot:actions>
            <x-button variant="secondary" icon="settings" href="{{ route('settings.index') }}">Settings</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($enabled)
        <x-card>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 ring-1 ring-emerald-200"><x-icon name="check-circle" class="w-5 h-5" /></span>
                <div>
                    <p class="text-sm font-semibold text-slate-900">Two-factor authentication is on.</p>
                    <p class="text-sm text-slate-500">You'll enter a code from your authenticator app when you sign in.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('settings.2fa.disable') }}" class="mt-6 border-t border-slate-100 pt-5 max-w-md space-y-4">
                @csrf @method('DELETE')
                <x-field label="Confirm Password To Turn Off" for="password" :error="$errors->first('password')">
                    <x-input id="password" name="password" type="password" autocomplete="current-password" required />
                </x-field>
                <x-button type="submit" variant="danger" icon="x">Turn Off 2FA</x-button>
            </form>
        </x-card>
    @elseif ($secret)
        <x-card title="Set Up Your Authenticator">
            <ol class="text-sm text-slate-600 space-y-4 list-decimal list-inside">
                <li>Open your authenticator app (Google Authenticator, Authy, 1Password…).</li>
                <li>Scan this QR code:
                    <div class="mt-3 flex flex-col items-center gap-2">
                        <div id="tfa-qr" data-uri="{{ $uri }}" class="bg-white p-3 rounded-xl ring-1 ring-slate-200 inline-flex"></div>
                        <p class="text-xs text-slate-400">Can’t scan? Enter the key manually below.</p>
                    </div>
                    <div class="mt-3 font-mono text-base tracking-widest bg-slate-50 ring-1 ring-slate-200 rounded-lg px-4 py-3 select-all">{{ trim(chunk_split($secret, 4, ' ')) }}</div>
                    <div class="mt-2 text-xs text-slate-400 break-all select-all">{{ $uri }}</div>
                </li>
                <li>Enter the 6-digit code it shows to confirm:</li>
            </ol>
            <script src="/vendor/qrcode.min.js"></script>
            <script>
                (function () {
                    var el = document.getElementById('tfa-qr');
                    if (el && window.QRCode && !el.hasChildNodes()) {
                        new QRCode(el, { text: el.dataset.uri, width: 176, height: 176, correctLevel: QRCode.CorrectLevel.M });
                    }
                })();
            </script>
            <form method="POST" action="{{ route('settings.2fa.confirm') }}" class="mt-5 flex items-end gap-3 max-w-sm">
                @csrf
                <x-field label="Code" for="code" :error="$errors->first('code')" class="flex-1">
                    <x-input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" placeholder="123456" autofocus data-lpignore="true" />
                </x-field>
                <x-button type="submit" icon="check">Confirm &amp; Enable</x-button>
            </form>
        </x-card>
    @else
        <x-card>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="max-w-md">
                    <p class="text-sm font-semibold text-slate-900">Add an extra layer of security.</p>
                    <p class="text-sm text-slate-500 mt-1">You'll need your password and a rotating 6-digit code to sign in.</p>
                </div>
                <form method="POST" action="{{ route('settings.2fa.enable') }}">@csrf
                    <x-button type="submit" icon="shield">Enable 2FA</x-button>
                </form>
            </div>
        </x-card>
    @endif
</x-layouts.app>
