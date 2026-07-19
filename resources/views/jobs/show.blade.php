@php
    $badge = ['success' => 'success', 'running' => 'info', 'queued' => 'neutral', 'warn' => 'warn', 'failed' => 'danger'];
    $fmt = function ($b) { if ($b === null) return '—'; $u=['B','KB','MB','GB','TB']; $i=0; while($b>=1024&&$i<4){$b/=1024;$i++;} return round($b,$i?1:0).' '.$u[$i]; };
    $p = $job->retentionPolicy;
@endphp
<x-layouts.app :title="$job->name">
    <x-page-header :title="$job->name" icon="clock"
        :subtitle="($job->host?->name ?? '') . ' · ' . ucfirst($job->type)">
        <x-slot:actions>
            <x-button variant="secondary" icon="edit" href="{{ route('jobs.edit', $job) }}">Edit</x-button>
            <x-confirm-action name="run-job-{{ $job->id }}" :action="route('jobs.run', $job)"
                title="Run This Backup Now?" message="Queues a run for this job. It executes on the next agent poll." confirm="Run Now" confirmIcon="play">
                <x-button icon="play">Run Now</x-button>
            </x-confirm-action>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            @php $recentRuns = $job->runs->sortByDesc('created_at')->take(15); @endphp
            <x-card title="Recent Runs" :flush="$job->runs->isNotEmpty()">
                @if ($job->runs->isEmpty())
                    <x-empty-state icon="archive" title="No Runs Yet" description="Trigger a run with the button above." />
                @else
                    <div
                        x-data="{
                            selected: [],
                            confirming: false,
                            allIds: [{{ $recentRuns->pluck('id')->implode(',') }}],
                            toggleAll(e) { this.selected = e.target.checked ? [...this.allIds] : []; this.confirming = false; },
                            submitBulk() {
                                const f = this.$refs.bulkForm;
                                f.querySelectorAll('input.js-dyn').forEach(n => n.remove());
                                this.selected.forEach(id => {
                                    const i = document.createElement('input');
                                    i.type = 'hidden'; i.name = 'ids[]'; i.value = id; i.className = 'js-dyn';
                                    f.appendChild(i);
                                });
                                f.submit();
                            }
                        }">
                        {{-- Hidden form the bulk delete posts through. --}}
                        <form method="POST" action="{{ route('runs.bulk-destroy') }}" x-ref="bulkForm" class="hidden">@csrf @method('DELETE')</form>

                        {{-- Bulk actions bar: appears once at least one run is selected. --}}
                        <div x-show="selected.length" x-cloak class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-brand-50 px-4 py-2.5">
                            <span class="text-sm font-medium text-brand-800"><span x-text="selected.length"></span> selected</span>
                            <div class="flex items-center gap-2">
                                <template x-if="! confirming">
                                    <x-button type="button" variant="danger" size="sm" icon="trash" x-on:click="confirming = true">Delete Selected</x-button>
                                </template>
                                <template x-if="confirming">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm text-brand-800">Delete <span x-text="selected.length"></span> run(s)?</span>
                                        <x-button type="button" variant="secondary" size="sm" x-on:click="confirming = false">Cancel</x-button>
                                        <x-button type="button" variant="danger" size="sm" icon="trash" x-on:click="submitBulk()">Confirm Delete</x-button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <x-table flush>
                            <thead><tr>
                                <th class="w-10">
                                    <button type="button" role="switch"
                                        :aria-checked="(allIds.length > 0 && selected.length === allIds.length).toString()"
                                        @click="selected = (allIds.length > 0 && selected.length === allIds.length) ? [] : [...allIds]"
                                        :class="(allIds.length > 0 && selected.length === allIds.length) ? 'bg-brand-600' : 'bg-slate-300'"
                                        class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors align-middle disabled:opacity-40"
                                        :disabled="allIds.length === 0" aria-label="Select all runs">
                                        <span :class="(allIds.length > 0 && selected.length === allIds.length) ? 'translate-x-6' : 'translate-x-1'"
                                            class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"></span>
                                    </button>
                                </th>
                                <th>Status</th><th>Snapshot</th><th>Size</th><th>When</th><th class="text-right">Actions</th>
                            </tr></thead>
                            <tbody>
                                @foreach ($recentRuns as $r)
                                    <tr class="cursor-pointer" onclick="window.location='{{ route('runs.show', $r) }}'">
                                        <td onclick="event.stopPropagation()">
                                            <button type="button" role="switch"
                                                :aria-checked="selected.includes({{ $r->id }}).toString()"
                                                @click="selected.includes({{ $r->id }}) ? selected.splice(selected.indexOf({{ $r->id }}), 1) : selected.push({{ $r->id }}); confirming = false"
                                                :class="selected.includes({{ $r->id }}) ? 'bg-brand-600' : 'bg-slate-300'"
                                                class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors align-middle"
                                                aria-label="Select run">
                                                <span :class="selected.includes({{ $r->id }}) ? 'translate-x-6' : 'translate-x-1'"
                                                    class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"></span>
                                            </button>
                                        </td>
                                        <td><x-badge :color="$badge[$r->status] ?? 'neutral'" dot>{{ ucfirst($r->status) }}</x-badge></td>
                                        <td class="font-mono text-xs text-slate-500"@if($r->snapshot_id) data-tip="{{ $r->snapshot_id }}" style="cursor:default"@endif>{{ $r->snapshot_id ? Str::limit($r->snapshot_id, 16) : '—' }}</td>
                                        <td>{{ $fmt($r->bytes_in) }}</td>
                                        <td class="text-slate-500">{{ $r->created_at?->diffForHumans() }}</td>
                                        <td class="text-right" onclick="event.stopPropagation()">
                                            <div class="inline-flex items-center gap-2">
                                                <x-icon-button :href="route('runs.show', $r)" icon="eye" title="View Log" />
                                                <x-delete-button :name="'del-run-' . $r->id" :action="route('runs.destroy', $r)"
                                                    title="Delete Run?" message="Removes this run record and its log. The snapshot in the repository is not deleted." confirm="Delete" label="Delete Run" />
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </x-table>
                    </div>
                @endif
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card title="Configuration">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Host</dt><dd class="font-medium text-slate-900">{{ $job->host?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Repository</dt><dd class="font-medium text-slate-900">{{ $job->repository?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Connector</dt><dd class="font-medium text-slate-900">{{ ucfirst($job->connector) }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Schedule</dt><dd class="font-medium text-slate-900 tabular">{{ $job->schedule_cron ?: 'Manual' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Enabled</dt><dd><x-badge :color="$job->enabled ? 'success' : 'neutral'">{{ $job->enabled ? 'On' : 'Off' }}</x-badge></dd></div>
                </dl>
            </x-card>

            <x-card title="Retention">
                @if ($p)
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-500">Latest</dt><dd class="tabular font-medium">{{ $p->keep_latest }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Daily</dt><dd class="tabular font-medium">{{ $p->keep_daily }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Weekly</dt><dd class="tabular font-medium">{{ $p->keep_weekly }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Monthly</dt><dd class="tabular font-medium">{{ $p->keep_monthly }}</dd></div>
                    </dl>
                    <p class="mt-4 text-xs text-slate-500">{{ $job->prune_after_backup ? 'Prunes after each backup.' : 'Prune after backup disabled.' }}{{ $job->prune_schedule_cron ? ' Separate prune: ' . $job->prune_schedule_cron : '' }}</p>
                @else
                    <p class="text-sm text-slate-500">No retention policy.</p>
                @endif
            </x-card>
        </div>
    </div>
</x-layouts.app>
