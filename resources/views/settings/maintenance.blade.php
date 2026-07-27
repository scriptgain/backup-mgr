<x-layouts.app title="Maintenance">
    <x-page-header title="Maintenance" icon="refresh" subtitle="Repository pruning and kopia maintenance windows." />

    {{-- Manual runs. Kept outside the settings form below: each action posts to
         its own endpoint, and nesting a form inside a form is invalid HTML. --}}
    <x-card title="Run Now" subtitle="Apply retention and reclaim space on demand, without waiting for the next backup." class="mb-6" flush>
        @if ($repositories->isEmpty())
            <x-empty-state icon="database" title="No Repositories"
                description="Add a repository before running maintenance." />
        @else
            <x-table flush>
                <thead>
                    <tr>
                        <th>Repository</th>
                        <th>Director</th>
                        <th>Agent</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($repositories as $r)
                        <tr>
                            <td>
                                <span class="font-medium text-slate-900">{{ $r->name }}</span>
                                <span class="ml-2 text-xs text-slate-400">{{ $r->jobs_count }} {{ Str::plural('job', $r->jobs_count) }}</span>
                            </td>
                            <td class="text-slate-500">{{ $r->director?->name ?? '-' }}</td>
                            <td>
                                @if (! $r->director?->gatewayAgent)
                                    <x-badge color="danger" dot>No Agent</x-badge>
                                @elseif (! $r->director->gatewayAgent->supportsMaintenanceTasks())
                                    <x-badge color="warn" dot>Agent {{ $r->director->gatewayAgent->agent_version ?: 'Unknown' }}</x-badge>
                                @else
                                    <span class="text-slate-500">{{ $r->director->gatewayAgent->name }}</span>
                                @endif
                            </td>
                            <td class="text-right">
                                @if ($r->activeMaintenanceTask)
                                    <x-badge :color="$r->activeMaintenanceTask->status === 'running' ? 'info' : 'neutral'" dot>
                                        {{ ucfirst($r->activeMaintenanceTask->status) }}: {{ $r->activeMaintenanceTask->kindLabel() }}
                                    </x-badge>
                                @else
                                    {{-- One trigger, three choices: three side-by-side buttons
                                         overflowed the fixed-width actions column. --}}
                                    <div class="inline-flex justify-end">
                                        <x-dropdown align="right" width="w-52">
                                            <x-slot:trigger>
                                                <x-button variant="secondary" size="sm" type="button" icon="chevron-down">Run</x-button>
                                            </x-slot:trigger>
                                            <x-dropdown-item icon="trash" x-on:click="$dispatch('open-modal', 'prune-{{ $r->id }}')">Prune Only</x-dropdown-item>
                                            <x-dropdown-item icon="refresh" x-on:click="$dispatch('open-modal', 'maint-{{ $r->id }}')">Maintenance Only</x-dropdown-item>
                                            <x-dropdown-item icon="check" x-on:click="$dispatch('open-modal', 'both-{{ $r->id }}')">Prune + Maintain</x-dropdown-item>
                                        </x-dropdown>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>

            <div class="px-5 sm:px-6 py-4 border-t border-slate-100">
                <p class="text-sm text-slate-500">
                    Retention counts come from each job's retention policy. Pruning expires snapshots; the freed space is
                    returned to the filesystem by maintenance, so <span class="font-medium text-slate-700">Prune + Maintain</span>
                    is the pass that shrinks a repository on disk.
                </p>
            </div>
        @endif
    </x-card>

    {{-- Confirm dialogs live outside the table on purpose: inside a cell they
         inherit .vx-table's nowrap, and the body text cannot wrap. --}}
    @foreach ($repositories as $r)
        @if (! $r->activeMaintenanceTask)
            <x-modal :name="'prune-' . $r->id" title="Prune This Repository?" icon="warning" tone="danger" maxWidth="max-w-md">
                Applies each job's retention policy to "{{ $r->name }}" and deletes the snapshots that fall outside it.
                Deleted snapshots cannot be restored.
                <x-slot:footer>
                    <x-button variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'prune-{{ $r->id }}')">Cancel</x-button>
                    <form method="POST" action="{{ route('settings.maintenance.run') }}">
                        @csrf
                        <input type="hidden" name="repository_id" value="{{ $r->id }}">
                        <input type="hidden" name="kind" value="prune">
                        <x-button variant="danger" size="sm" icon="trash" type="submit">Prune Now</x-button>
                    </form>
                </x-slot:footer>
            </x-modal>

            <x-modal :name="'maint-' . $r->id" title="Run Repository Maintenance?" icon="info" maxWidth="max-w-md">
                Runs kopia full maintenance on "{{ $r->name }}": compaction and garbage collection, which reclaim the space
                already freed by expired snapshots. Nothing is deleted, but it can take a while on a large repository.
                <x-slot:footer>
                    <x-button variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'maint-{{ $r->id }}')">Cancel</x-button>
                    <form method="POST" action="{{ route('settings.maintenance.run') }}">
                        @csrf
                        <input type="hidden" name="repository_id" value="{{ $r->id }}">
                        <input type="hidden" name="kind" value="maintenance">
                        <x-button variant="primary" size="sm" icon="refresh" type="submit">Run Maintenance</x-button>
                    </form>
                </x-slot:footer>
            </x-modal>

            <x-modal :name="'both-' . $r->id" title="Prune And Maintain?" icon="warning" tone="danger" maxWidth="max-w-md">
                Applies retention to "{{ $r->name }}", deletes the expired snapshots, then compacts and garbage-collects the
                repository. This is the pass that shrinks it on disk. Deleted snapshots cannot be restored.
                <x-slot:footer>
                    <x-button variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'both-{{ $r->id }}')">Cancel</x-button>
                    <form method="POST" action="{{ route('settings.maintenance.run') }}">
                        @csrf
                        <input type="hidden" name="repository_id" value="{{ $r->id }}">
                        <input type="hidden" name="kind" value="both">
                        <x-button variant="danger" size="sm" icon="check" type="submit">Prune + Maintain</x-button>
                    </form>
                </x-slot:footer>
            </x-modal>
        @endif
    @endforeach

    @if ($tasks->isNotEmpty())
        <x-card title="Recent Manual Runs" subtitle="The last ten prune, maintenance and snapshot delete tasks." class="mb-6" flush>
            <x-table flush>
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Repository</th>
                        <th>Status</th>
                        <th>Duration</th>
                        <th>Result</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tasks as $t)
                        <tr>
                            <td>
                                <span class="font-medium text-slate-900">{{ $t->kindLabel() }}@if ($t->deletesNamedSnapshots())<span class="font-normal text-slate-400"> ({{ count($t->snapshotIds()) }})</span>@endif</span>
                                <span class="block text-xs text-slate-400">
                                    {{ $t->created_at?->diffForHumans() }}{{ $t->user ? ' by ' . $t->user->name : '' }}
                                </span>
                            </td>
                            <td class="text-slate-500">{{ $t->repository?->name ?? '-' }}</td>
                            <td><x-badge :color="$t->statusColor()" dot>{{ ucfirst($t->status) }}</x-badge></td>
                            <td class="text-slate-500">{{ $t->duration() ?? '-' }}</td>
                            <td class="text-slate-500">{{ $t->error ?: ($t->log ?: '-') }}</td>
                            <td class="text-right">
                                @if ($t->status === 'queued')
                                    <x-button variant="secondary" size="sm" type="button"
                                        x-on:click="$dispatch('open-modal', 'cancel-task-{{ $t->id }}')">Cancel</x-button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
            @if ($tasks->contains(fn ($t) => $t->isActive()))
                {{-- A task in flight resolves on the agent's next check-in, so refresh
                     rather than leaving a stale "queued" row on screen. --}}
                <script>setTimeout(function () { window.location.reload(); }, 15000);</script>
            @endif
        </x-card>

        @foreach ($tasks->where('status', 'queued') as $t)
            <x-modal :name="'cancel-task-' . $t->id" title="Cancel This Task?" icon="warning" tone="danger" maxWidth="max-w-md">
                The task is still queued, so cancelling stops it before any agent picks it up.
                <x-slot:footer>
                    <x-button variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'cancel-task-{{ $t->id }}')">Keep It</x-button>
                    <form method="POST" action="{{ route('settings.maintenance.cancel', $t) }}">
                        @csrf @method('DELETE')
                        <x-button variant="danger" size="sm" type="submit">Cancel Task</x-button>
                    </form>
                </x-slot:footer>
            </x-modal>
        @endforeach
    @endif

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
