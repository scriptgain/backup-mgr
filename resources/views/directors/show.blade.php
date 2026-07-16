@php
    $statusColor = ['online' => 'success', 'offline' => 'danger', 'pending' => 'warn', 'stale' => 'warn'];
    $connLabel = ['agent' => 'Agent', 'ssh' => 'SSH', 'sftp' => 'SFTP', 'ftp' => 'FTP', 'rsync' => 'Rsync', 's3' => 'S3'];
    $fmtBytes = function ($b) { if ($b === null) return '—'; $u=['B','KB','MB','GB','TB']; $i=0; while($b>=1000&&$i<4){$b/=1000;$i++;} return round($b,$i?1:0).' '.$u[$i]; };
@endphp
<x-layouts.app :title="$director->name">
    <x-page-header :title="$director->name" icon="cloud"
        :subtitle="collect([$director->location?->name, $director->hostname])->filter()->implode(' · ') ?: 'Director node'">
        <x-slot:actions>
            <x-button variant="secondary" icon="edit" href="{{ route('directors.edit', $director) }}">Edit</x-button>
            <x-button icon="plus" href="{{ route('hosts.create', $director) }}">Add Host</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <x-stat label="Location" value="{{ $director->location?->name ?? 'Unassigned' }}" icon="folder" />
        <x-stat label="Address" value="{{ $director->hostname ?: '—' }}" icon="server" />
        <x-stat label="Hosts" value="{{ $director->hosts->count() }}" icon="database" />
        <x-stat label="Status" value="{{ ucfirst($director->status) }}" icon="cloud" />
    </div>

    <x-card title="Hosts" subtitle="Machines protected under this Director" :flush="$director->hosts->isNotEmpty()">
        @if ($director->hosts->isEmpty())
            <x-empty-state icon="server" title="No Hosts Yet" description="Add a host via agent, SSH, SFTP, FTP, rsync, or S3.">
                <x-slot:action><x-button icon="plus" href="{{ route('hosts.create', $director) }}">Add Host</x-button></x-slot:action>
            </x-empty-state>
        @else
            <x-table flush>
                <thead>
                    <tr><th>Name</th><th>Connection</th><th>Address</th><th>Disks</th><th>Status</th><th class="text-right">Actions</th></tr>
                </thead>
                <tbody>
                    @foreach ($director->hosts as $h)
                        <tr>
                            <td class="font-medium text-slate-900"><a href="{{ route('hosts.show', $h) }}" class="hover:text-brand-700">{{ $h->name }}</a></td>
                            <td><x-badge color="neutral">{{ $connLabel[$h->connection_type] ?? $h->connection_type }}</x-badge></td>
                            <td class="text-slate-500 font-mono text-xs">{{ $h->ip_address ?: ($h->hostname ?: '—') }}</td>
                            <td class="tabular">{{ is_array($h->disks) ? count($h->disks) : 0 }}</td>
                            <td><x-badge :color="$statusColor[$h->effective_status] ?? 'neutral'" dot>{{ ucfirst($h->effective_status) }}</x-badge></td>
                            <td class="text-right">
                                <div class="inline-flex items-center gap-2">
                                    <x-icon-button :href="route('hosts.show', $h)" icon="eye" title="Open" />
                                    <x-icon-button :href="route('hosts.edit', $h)" icon="edit" title="Edit" />
                                    <x-delete-button :name="'del-host-' . $h->id" :action="route('hosts.destroy', $h)"
                                        title="Remove Host?" message="This removes the host, its jobs, and their run history — those snapshots stop being listed here. Data already written to the repository is not removed." />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        @endif
    </x-card>

    <div class="mt-6">
        <x-card title="Storage Devices" subtitle="Disks on this node where filesystem repositories can be placed">
            <x-slot:actions>
                <form method="POST" action="{{ route('directors.storage.detect', $director) }}">@csrf
                    <x-button type="submit" variant="secondary" size="sm" icon="sync">Detect Disks</x-button>
                </form>
            </x-slot:actions>
            @if ($director->storageDevices->isEmpty())
                <p class="text-sm text-slate-500">No storage devices defined yet. Click <span class="font-medium">Detect Disks</span> to auto-add this node's disks, or add mount points below.</p>
            @else
                <div class="space-y-4">
                    @foreach ($director->storageDevices as $dev)
                        <div class="flex items-center gap-4">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-medium text-slate-900 truncate">{{ $dev->name }} <span class="font-mono text-xs text-slate-400">{{ $dev->mount_path }}</span></p>
                                    <p class="text-xs text-slate-500 tabular shrink-0">
                                        @if ($dev->total_bytes){{ $fmtBytes($dev->used_bytes ?? 0) }} / {{ $fmtBytes($dev->total_bytes) }}@else capacity unknown @endif
                                    </p>
                                </div>
                                @if ($dev->total_bytes)
                                    <div class="mt-1.5 h-2 w-full rounded-full bg-slate-100 overflow-hidden">
                                        <div class="h-full rounded-full {{ ($dev->usedPercent() ?? 0) > 90 ? 'bg-rose-500' : 'bg-brand-500' }}" style="width: {{ min(100, $dev->usedPercent() ?? 0) }}%"></div>
                                    </div>
                                @endif
                            </div>
                            <x-delete-button :name="'del-dev-' . $dev->id" :action="route('storage.destroy', $dev)"
                                title="Remove Storage Device?" message="Repositories pointed at this device will keep their configured path." />
                        </div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('directors.storage.store', $director) }}" class="mt-5 border-t border-slate-100 pt-5">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <x-field label="Name" for="dev_name"><x-input id="dev_name" name="name" placeholder="e.g. Data Disk 1" required /></x-field>
                    <x-field label="Mount Path" for="dev_path"><x-input id="dev_path" name="mount_path" placeholder="/mnt/data1" required /></x-field>
                    <x-field label="Total (GB)" for="dev_total"><x-input id="dev_total" name="total_gb" type="number" step="1" placeholder="500" /></x-field>
                    <x-field label="Used (GB)" for="dev_used"><x-input id="dev_used" name="used_gb" type="number" step="1" placeholder="0" /></x-field>
                </div>
                <div class="mt-4 flex justify-end">
                    <x-button type="submit" icon="plus" size="sm">Add Storage Device</x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.app>
