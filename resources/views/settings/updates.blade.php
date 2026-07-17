<x-layouts.app title="Software Updates">
    <x-page-header title="Software Updates" icon="refresh" subtitle="Keep this install on the latest signed release." />

    @if (session('status'))
        <div class="mb-5 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-800 ring-1 ring-brand-100">{{ session('status') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-card title="Version" subtitle="Your installed build versus the latest release published for your license.">
                <div class="flex flex-wrap items-center gap-x-10 gap-y-4">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Installed</p>
                        <p class="mt-1 text-2xl font-semibold text-slate-900 tabular">{{ $status['current'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Latest</p>
                        <p class="mt-1 text-2xl font-semibold text-slate-900 tabular">{{ $status['latest'] ?: '—' }}</p>
                    </div>
                    <div>
                        @if ($status['available'])
                            <x-badge color="warn" dot>Update Available</x-badge>
                        @else
                            <x-badge color="success" dot>Up To Date</x-badge>
                        @endif
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-5">
                    <form method="POST" action="{{ route('settings.updates.check') }}">
                        @csrf
                        <x-button type="submit" variant="secondary" size="sm" icon="refresh">Check For Updates</x-button>
                    </form>
                    @if ($status['available'])
                        <form method="POST" action="{{ route('settings.updates.apply') }}"
                              x-data x-on:submit="$el.querySelector('button').disabled = true">
                            @csrf
                            <x-button type="submit" size="sm" icon="download">Update To {{ $status['latest'] }}</x-button>
                        </form>
                    @endif
                    @if ($status['checked_at'])
                        <span class="text-xs text-slate-400">Last checked {{ \Illuminate\Support\Carbon::parse($status['checked_at'])->diffForHumans() }}</span>
                    @endif
                </div>

                @if ($status['last_result'])
                    <p class="mt-4 text-sm {{ str_starts_with($status['last_result'], 'error') ? 'text-rose-600' : 'text-slate-500' }}">{{ $status['last_result'] }}</p>
                @endif
            </x-card>

            <x-card title="Automatic Updates" subtitle="When on, this install applies new signed releases on its own overnight.">
                <form method="POST" action="{{ route('settings.updates.auto') }}" x-on:change="$el.submit()">
                    @csrf
                    <style>
                        .up-sw{position:relative;display:inline-flex;height:1.5rem;width:2.75rem;flex:0 0 auto;align-items:center;border-radius:9999px;background:#cbd5e1;transition:background .15s;}
                        .up-sw input{position:absolute;opacity:0;width:0;height:0;margin:0;}
                        .up-sw i{position:absolute;left:.25rem;height:1rem;width:1rem;border-radius:9999px;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.25);transition:transform .15s;}
                        .up-sw input:checked ~ i{transform:translateX(1.25rem);}
                        .up-sw:has(input:checked){background:var(--color-brand-600,#4f46e5);}
                    </style>
                    {{-- Native checkbox so toggling fires a real change event and the form saves. --}}
                    <label class="flex items-start gap-3 cursor-pointer select-none">
                        <input type="hidden" name="auto" value="0">
                        <span class="up-sw"><input type="checkbox" name="auto" value="1" @checked($status['auto'])><i></i></span>
                        <span class="text-sm">
                            <span class="font-medium text-slate-900">Install Updates Automatically</span>
                            <span class="block text-slate-500">Nightly, the install checks for a newer signed release and applies it. A backup of the current build is kept before each update.</span>
                        </span>
                    </label>
                </form>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card title="How Updates Work" flush>
                <div class="p-5 space-y-3 text-sm text-slate-600">
                    <p>Update availability comes from your <strong>signed license response</strong>, so the version and download are verified against the vendor key.</p>
                    <p>Each release tarball is checksum-verified before it is applied, and the previous build is archived under <code class="text-xs">storage/app/private/updates</code> in case a rollback is needed.</p>
                    <p>Backups keep running throughout; only new code, migrations, and caches are touched.</p>
                </div>
            </x-card>
        </div>
    </div>
</x-layouts.app>
