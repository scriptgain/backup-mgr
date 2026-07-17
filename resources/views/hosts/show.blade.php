@php
    $statusColor = ['online' => 'success', 'offline' => 'danger', 'pending' => 'warn', 'stale' => 'warn'];
    $connLabel = ['agent' => 'Agent', 'ssh' => 'SSH', 'sftp' => 'SFTP', 'ftp' => 'FTP', 'rsync' => 'Rsync', 'multiftp' => 'Multi-FTP', 's3' => 'S3'];
@endphp
<x-layouts.app :title="$host->name">
    @if (session('conn_test'))
        @php [$ct, $ctMsg] = array_pad(explode(':', session('conn_test'), 2), 2, ''); $ctType = ['ok'=>'success','fail'=>'danger','pending'=>'warn'][$ct] ?? 'info'; @endphp
        <div class="mb-6"><x-alert :type="$ctType" :title="$ct === 'ok' ? 'Connection OK' : ($ct === 'fail' ? 'Connection Failed' : 'Heads Up')">{{ $ctMsg }}</x-alert></div>
    @endif
    <x-page-header :title="$host->name" icon="server"
        :subtitle="'Director: ' . $host->director->name">
        <x-slot:actions>
            <x-badge :color="$statusColor[$host->effective_status] ?? 'neutral'" dot>{{ ucfirst($host->effective_status) }}</x-badge>
            <x-button variant="secondary" icon="edit" href="{{ route('hosts.edit', $host) }}">Edit</x-button>
            @if ($repositories->isNotEmpty())
                <div x-data="{ qbOpen: false }" class="inline-flex">
                    <x-button type="button" variant="secondary" icon="play" @click="qbOpen = true">Quick Backup</x-button>
                    <div x-show="qbOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
                        style="background-color: rgba(15,23,42,.55)" @keydown.escape.window="qbOpen = false">
                        <div class="w-full max-w-md bg-white rounded-xl shadow-2xl ring-1 ring-slate-200" @click.outside="qbOpen = false">
                            <form method="POST" action="{{ route('hosts.quickBackup', $host) }}">
                                @csrf
                                <div class="flex items-center justify-between px-5 py-3 border-b border-slate-100">
                                    <h3 class="text-sm font-semibold text-slate-900 flex items-center gap-2"><x-icon name="play" class="w-4 h-4 text-brand-600" /> Quick Backup</h3>
                                    <button type="button" @click="qbOpen = false" class="text-slate-400 hover:text-slate-600"><x-icon name="x" class="w-5 h-5" /></button>
                                </div>
                                <div class="px-5 py-4 space-y-4 text-left">
                                    <p class="text-sm text-slate-600">Runs a one-time backup right now to confirm the connection and pipeline work end to end. It does not create a saved job or a schedule.</p>
                                    <x-field label="Path To Back Up" for="qb_path" hint="A directory on the host.">
                                        <x-input id="qb_path" name="path" value="{{ (is_array($host->disks) && count($host->disks)) ? $host->disks[0] : '/' }}" required />
                                    </x-field>
                                    <x-field label="Repository" for="qb_repo">
                                        <x-select id="qb_repo" name="repository_id">
                                            @foreach ($repositories as $repo)
                                                <option value="{{ $repo->id }}" @selected($defaultRepoId === $repo->id)>{{ $repo->name }}</option>
                                            @endforeach
                                        </x-select>
                                    </x-field>
                                </div>
                                <div class="flex items-center justify-end gap-2 px-5 py-3 border-t border-slate-100">
                                    <x-button type="button" variant="secondary" size="sm" @click="qbOpen = false">Cancel</x-button>
                                    <x-button type="submit" size="sm" icon="play">Run Backup Now</x-button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
            <x-confirm-action name="backup-host-{{ $host->id }}" :action="route('hosts.backup', $host)"
                title="Run Backup Now?" message="This queues a run for every enabled job on this host." confirm="Back Up Now" confirmIcon="play">
                <x-button icon="play">Back Up Now</x-button>
            </x-confirm-action>
            <x-delete-button :name="'del-host-' . $host->id" :action="route('hosts.destroy', $host)"
                title="Remove Host?" message="This removes the host, its jobs, and their run history — those snapshots stop being listed here. Data already written to the repository is not removed." />
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-card title="Connection">
                @if ($host->connection_type !== 'agent')
                    <x-slot:actions>
                        <form method="POST" action="{{ route('hosts.test', $host) }}">@csrf
                            <x-button type="submit" variant="secondary" size="sm" icon="check">Test Connection</x-button>
                        </form>
                    </x-slot:actions>
                @endif
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div><dt class="text-slate-500">Type</dt><dd class="mt-0.5 font-medium text-slate-900">{{ $connLabel[$host->connection_type] ?? $host->connection_type }}</dd></div>
                    <div><dt class="text-slate-500">IP Address</dt><dd class="mt-0.5 font-medium text-slate-900">{{ $host->ip_address ?: '—' }}</dd></div>
                    <div><dt class="text-slate-500">Hostname</dt><dd class="mt-0.5 font-medium text-slate-900">{{ $host->hostname ? $host->hostname . ($host->port ? ':' . $host->port : '') : '—' }}</dd></div>
                    <div><dt class="text-slate-500">Username</dt><dd class="mt-0.5 font-medium text-slate-900">{{ $host->username ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Auth</dt><dd class="mt-0.5 font-medium text-slate-900">{{ $host->auth_type ? ucfirst($host->auth_type) : '—' }}</dd></div>
                    <div><dt class="text-slate-500">Last Seen</dt><dd class="mt-0.5 font-medium text-slate-900">{{ $host->last_seen_at?->diffForHumans() ?? 'Never' }}</dd></div>
                    <div><dt class="text-slate-500">Agent Version</dt><dd class="mt-0.5 font-medium text-slate-900">{{ $host->agent_version ?? '—' }}</dd></div>
                </dl>
            </x-card>

            <x-card title="Backup Jobs" :flush="$host->jobs->isNotEmpty()">
                <x-slot:actions>
                    <x-button size="sm" icon="plus" href="{{ route('jobs.index') }}">New Job</x-button>
                </x-slot:actions>
                @if ($host->jobs->isEmpty())
                    <x-empty-state icon="clock" title="No Jobs Yet" description="Create a backup job to protect this host on a schedule." />
                @else
                    <x-table flush>
                        <thead><tr><th>Name</th><th>Type</th><th>Schedule</th><th>Enabled</th></tr></thead>
                        <tbody>
                            @foreach ($host->jobs as $j)
                                <tr>
                                    <td class="font-medium text-slate-900">{{ $j->name }}</td>
                                    <td>{{ ucfirst($j->type) }}</td>
                                    <td class="tabular">{{ $j->schedule_cron ?? 'Manual' }}</td>
                                    <td><x-badge :color="$j->enabled ? 'success' : 'neutral'">{{ $j->enabled ? 'On' : 'Off' }}</x-badge></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-table>
                @endif
            </x-card>
        </div>

        <div class="space-y-6">
            @if ($host->connection_type === 'agent')
                <x-card title="Agent Enrollment">
                    @if (session('enroll_token'))
                        <p class="text-sm text-slate-600">Run this on the host as root (copy now — shown once):</p>
                        <pre class="mt-2 rounded-lg bg-chrome text-slate-100 text-xs p-3 overflow-x-auto"><code>curl -fsSL {{ config('app.url') }}/downloads/agent-install.sh \
  | sudo bash -s -- {{ config('app.url') }} {{ session('enroll_token') }}</code></pre>
                        <p class="mt-2 text-xs text-slate-500">Installs the agent + kopia, enrolls this host, and starts a systemd service. The agent only dials out.</p>
                    @else
                        <p class="text-sm text-slate-500">Generate a one-time token, then run the install one-liner on the host. The agent dials out only — no inbound ports.</p>
                    @endif
                    @if ($host->api_key)
                        <p class="mt-4 text-xs text-emerald-600 flex items-center gap-1.5"><x-icon name="check-circle" class="w-4 h-4" /> Agent enrolled.</p>
                        <x-confirm-action
                            :name="'rotate-key-' . $host->id"
                            :action="route('hosts.enroll', $host)"
                            title="Rotate Agent Key?"
                            message="This revokes the current agent key immediately. The running agent stops working until you re-run the install command with the new token. Proceed?"
                            confirm="Rotate Key" confirmVariant="danger" confirmIcon="key" tone="danger">
                            <x-button type="button" icon="key" variant="secondary" size="sm" class="mt-3 w-full">Rotate Agent Key</x-button>
                        </x-confirm-action>
                    @else
                        <form method="POST" action="{{ route('hosts.enroll', $host) }}" class="mt-4">
                            @csrf
                            <x-button type="submit" icon="key" variant="secondary" size="sm" class="w-full">Generate Enrollment Token</x-button>
                        </form>
                    @endif
                </x-card>
            @endif

            <x-card title="Files">
                <x-slot:actions>
                    <x-host-file-browser :host="$host" mode="view" label="Open File Manager" />
                </x-slot:actions>
                @if (is_array($host->disks) && count($host->disks))
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400 mb-2">Backed-Up Paths</p>
                    <ul class="space-y-2 text-sm">
                        @foreach ($host->disks as $disk)
                            <li class="flex items-center gap-2 text-slate-700">
                                <x-icon name="folder" class="w-4 h-4 text-slate-400 shrink-0" />
                                <span class="font-mono text-xs break-all">{{ $disk }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-slate-500">No specific paths selected — backups cover the whole login directory. Open the file manager to view this host's files live, or pick paths on the Edit screen.</p>
                @endif
            </x-card>
        </div>
    </div>
</x-layouts.app>
