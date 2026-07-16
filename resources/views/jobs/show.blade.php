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
            <x-card title="Recent Runs" :flush="$job->runs->isNotEmpty()">
                @if ($job->runs->isEmpty())
                    <x-empty-state icon="archive" title="No Runs Yet" description="Trigger a run with the button above." />
                @else
                    <x-table flush>
                        <thead><tr><th>Status</th><th>Snapshot</th><th>Size</th><th>When</th><th class="text-right">Actions</th></tr></thead>
                        <tbody>
                            @foreach ($job->runs->sortByDesc('created_at')->take(15) as $r)
                                <tr class="cursor-pointer" onclick="window.location='{{ route('runs.show', $r) }}'">
                                    <td><x-badge :color="$badge[$r->status] ?? 'neutral'" dot>{{ ucfirst($r->status) }}</x-badge></td>
                                    <td class="font-mono text-xs text-slate-500">{{ $r->snapshot_id ? Str::limit($r->snapshot_id, 16) : '—' }}</td>
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
