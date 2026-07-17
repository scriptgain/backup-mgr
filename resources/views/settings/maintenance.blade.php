<x-layouts.app title="Maintenance">
    <x-page-header title="Maintenance" icon="refresh" subtitle="Repository pruning and kopia maintenance windows." />

    <form method="POST" action="{{ route('settings.maintenance.update') }}" class="space-y-6">
        @csrf @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">

                {{-- Automatic maintenance --}}
                <x-card title="Automatic Maintenance" subtitle="Compaction and garbage collection that reclaim space in kopia repositories.">
                    <x-toggle name="auto_maintenance" :checked="$v['auto_maintenance'] === '1'"
                        label="Run Repository Maintenance"
                        description="After a successful backup, run kopia compaction and GC to keep repositories healthy and small." />
                </x-card>

                {{-- Maintenance window --}}
                <x-card title="Maintenance Window" subtitle="Confine maintenance to off-peak hours so it never competes with production load.">
                    <x-toggle name="maintenance_window_enabled" :checked="$v['maintenance_window_enabled'] === '1'"
                        label="Restrict To A Window"
                        description="When on, maintenance runs only inside the days and hours below. When off, it may run after any backup." />

                    <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-5 border-t border-slate-100 pt-5">
                        <x-field label="Window Start" for="maintenance_window_start" :error="$errors->first('maintenance_window_start')"
                            hint="Local time in {{ config('app.timezone') }}.">
                            <x-input type="time" id="maintenance_window_start" name="maintenance_window_start" value="{{ $v['maintenance_window_start'] }}" />
                        </x-field>
                        <x-field label="Window End" for="maintenance_window_end" :error="$errors->first('maintenance_window_end')"
                            hint="A window that ends before it starts wraps past midnight.">
                            <x-input type="time" id="maintenance_window_end" name="maintenance_window_end" value="{{ $v['maintenance_window_end'] }}" />
                        </x-field>
                    </div>

                    <div class="mt-5 border-t border-slate-100 pt-5">
                        <span class="block text-sm font-medium text-slate-700 mb-2">Days Maintenance May Run</span>
                        <div class="flex flex-wrap gap-x-6 gap-y-3">
                            @foreach ($days as $day)
                                <x-check-switch name="maintenance_days[]" :value="$day" :checked="in_array($day, $selectedDays, true)" class="capitalize">{{ $day }}</x-check-switch>
                            @endforeach
                        </div>
                        @error('maintenance_days.*')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </x-card>

                {{-- Repository pruning --}}
                <x-card title="Repository Pruning" subtitle="Apply retention and expire old snapshots to keep repositories from growing without bound.">
                    <x-toggle name="prune_all_jobs" :checked="$v['prune_all_jobs'] === '1'"
                        label="Prune After Every Backup (All Jobs)"
                        description="Force retention + space reclaim after every successful run on every job, overriding each job's own prune toggle." />
                    <p class="mt-4 text-sm text-slate-500">
                        Retention counts (keep latest / daily / weekly / monthly) come from each job's retention policy.
                        Set fleet-wide defaults under
                        <a href="{{ route('settings.general.edit') }}" class="text-brand-600 hover:underline">General → Backup Defaults</a>.
                    </p>
                </x-card>

            </div>

            {{-- Sidebar: live status --}}
            <div class="space-y-6">
                <x-card title="Status">
                    <dl class="divide-y divide-slate-100 text-sm">
                        <div class="flex items-center justify-between gap-4 py-2.5">
                            <dt class="text-slate-500 shrink-0">Right Now</dt>
                            <dd class="text-right">
                                @if ($allowedNow)
                                    <x-badge color="success">Maintenance Allowed</x-badge>
                                @else
                                    <x-badge color="neutral">Outside Window</x-badge>
                                @endif
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 py-2.5">
                            <dt class="text-slate-500 shrink-0">Server Time</dt>
                            <dd class="font-medium text-slate-900 text-right">{{ $now->format('g:i A T') }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 py-2.5">
                            <dt class="text-slate-500 shrink-0">Repositories</dt>
                            <dd class="font-medium text-slate-900 text-right">{{ $repositoryCount }}</dd>
                        </div>
                    </dl>
                    <p class="mt-4 text-xs text-slate-400">
                        Agents evaluate the window each time they check in; a backup that finishes outside the window skips maintenance until the next in-window run.
                    </p>
                </x-card>
            </div>
        </div>

        <div class="flex justify-end gap-3 sticky bottom-4">
            <div class="flex gap-3 rounded-xl bg-white/90 backdrop-blur ring-1 ring-slate-200 shadow-sm px-4 py-3">
                <x-button variant="secondary" type="button" onclick="window.location.reload()">Reset</x-button>
                <x-button variant="primary" type="submit" icon="check">Save Settings</x-button>
            </div>
        </div>
    </form>
</x-layouts.app>
