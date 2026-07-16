<x-layouts.app title="Backup Jobs">
    <x-page-header title="Backup Jobs" icon="clock" subtitle="What gets backed up, on what schedule, with what retention.">
        <x-slot:actions>
            <x-button icon="plus" href="{{ route('jobs.create') }}">New Job</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($jobs->isEmpty())
        <x-card>
            <x-empty-state icon="clock" title="No Jobs Yet" description="Create a backup job on a host and point it at a repository.">
                <x-slot:action><x-button icon="plus" href="{{ route('jobs.create') }}">New Job</x-button></x-slot:action>
            </x-empty-state>
        </x-card>
    @else
        <x-table>
            <thead>
                <tr><th>Name</th><th>Host</th><th>Type</th><th>Schedule</th><th>Enabled</th><th class="text-right">Actions</th></tr>
            </thead>
            <tbody>
                @foreach ($jobs as $j)
                    <tr>
                        <td class="font-medium text-slate-900"><a href="{{ route('jobs.show', $j) }}" class="hover:text-brand-700">{{ $j->name }}</a></td>
                        <td>{{ $j->host?->name ?? '—' }}</td>
                        <td>{{ ucfirst($j->type) }}</td>
                        <td class="tabular text-slate-500">{{ $j->schedule_cron ?: 'Manual' }}</td>
                        <td><x-badge :color="$j->enabled ? 'success' : 'neutral'">{{ $j->enabled ? 'On' : 'Off' }}</x-badge></td>
                        <td class="text-right">
                            <div class="inline-flex items-center gap-2">
                                <x-confirm-action name="run-{{ $j->id }}" :action="route('jobs.run', $j)"
                                    title="Run This Backup Now?" message="Queues a run for this job. It executes on the next agent poll." confirm="Run Now" confirmIcon="play">
                                    <x-icon-button type="button" icon="play" title="Run Now" variant="brand" />
                                </x-confirm-action>
                                <x-icon-button :href="route('jobs.show', $j)" icon="eye" title="Open" />
                                <x-icon-button :href="route('jobs.edit', $j)" icon="edit" title="Edit" />
                                <x-delete-button :name="'del-job-' . $j->id" :action="route('jobs.destroy', $j)"
                                    title="Delete Job?" message="This deletes the job, its schedule, and its run history — those snapshots stop being listed here. Data already written to the repository is not removed." />
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
    @endif
</x-layouts.app>
