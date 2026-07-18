@php
    $statusColor = ['online' => 'success', 'offline' => 'danger', 'pending' => 'warn', 'stale' => 'warn'];
    $connLabel = ['agent' => 'Agent', 'ssh' => 'SSH', 'sftp' => 'SFTP', 'ftp' => 'FTP', 'rsync' => 'Rsync', 'multiftp' => 'Multi-FTP', 's3' => 'S3 Compatible', 'ingest' => 'Ingest'];
    // Endpoint an external system points its push destination at: the operator's
    // explicit IP override, else the Director gateway's hostname, else this app's host.
    $ingestEndpoint = $host->ip_address ?: ($host->director->hostname ?: (parse_url(config('app.url'), PHP_URL_HOST) ?: request()->getHost()));
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
                @if (! in_array($host->connection_type, ['agent', 'ingest']))
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

            @if ($host->connection_type === 'ingest')
                @php
                    $ingestReady = in_array($host->ingest_protocol, ['sftp', 'ftp', 's3'], true);
                    $isFtp = $host->ingest_protocol === 'ftp';
                    $isS3 = $host->ingest_protocol === 's3';
                    $pasvRange = config('backup.ingest_ftp_pasv_min', 30000) . '–' . config('backup.ingest_ftp_pasv_max', 30100);
                @endphp
                <x-card title="Ingest Connection Details"
                    x-data="{ show: false, copied: '', copy(v, k) { navigator.clipboard.writeText(v); this.copied = k; setTimeout(() => this.copied = '', 1300); } }">
                    <x-slot:actions>
                        <x-badge :color="$ingestReady ? 'success' : 'warn'" dot>{{ strtoupper($host->ingest_protocol) }}{{ $ingestReady ? '' : ' · coming soon' }}</x-badge>
                    </x-slot:actions>

                    @if ($isS3)
                        <x-alert type="info" title="Point your cPanel/WHM/ResellerPanel S3 destination here" class="mb-4">
                            Add an <strong>S3</strong> (Amazon S3 / custom) backup destination in your panel using the endpoint and credentials below. Use <strong>path-style</strong> addressing, region <code>us-east-1</code>, and allow the self-signed TLS certificate. Your panel <strong>pushes</strong> account backups here (PutObject / multipart) and BackupMGR snapshots them into the repository on the schedule. This is a <strong>receive gateway</strong>, distinct from the future StorageMGR object store.
                        </x-alert>
                    @elseif ($isFtp)
                        <x-alert type="info" title="Point your cPanel/WHM FTP(S) destination here" class="mb-4">
                            In cPanel <strong>Backup → Additional Destinations</strong> (or WHM <strong>Backup Configuration → Additional Destinations → FTP</strong>), add an <strong>FTP</strong> destination using the details below and enable <strong>passive mode</strong> and <strong>TLS (FTPS)</strong>. cPanel/WHM will <strong>push</strong> its backups to this drop folder; BackupMGR snapshots them into the repository on the schedule. Any tool that can <code>ftp</code>/<code>lftp</code>/<code>curl</code> with these credentials works too.
                        </x-alert>
                    @elseif ($ingestReady)
                        <x-alert type="info" title="Point your cPanel/WHM SFTP destination here" class="mb-4">
                            In cPanel <strong>Backup → Additional Destinations</strong> (or WHM <strong>Backup Configuration → Additional Destinations → SFTP</strong>), add an <strong>SFTP</strong> destination using the details below. cPanel/WHM will <strong>push</strong> its backups to this drop folder; BackupMGR snapshots them into the repository on the schedule. Files also arrive from any tool that can <code>sftp</code>/<code>scp</code> with these credentials.
                        </x-alert>
                    @else
                        <x-alert type="warn" title="This protocol isn't receiving yet" class="mb-4">
                            Switch this connection to <strong>SFTP</strong>, <strong>FTP/FTPS</strong>, or <strong>S3</strong> on the Edit screen to receive backups.
                        </x-alert>
                    @endif

                    @php
                        if ($isS3) {
                            $rows = [
                                ['Endpoint URL', $host->ingestS3Endpoint(), 'endpoint'],
                                ['Bucket', $host->ingestBucket() ?: '—', 'bucket'],
                                ['Region', 'us-east-1', 'region'],
                                ['Addressing', 'Path-style', null],
                                ['Access Key', $host->username ?: '—', 'user'],
                            ];
                        } else {
                            $rows = [
                                ['Host', $ingestEndpoint, 'host'],
                                [$isFtp ? 'Control Port' : 'Port', (string) $host->ingestPort(), 'port'],
                                ['Protocol', strtoupper($host->ingest_protocol), null],
                            ];
                            if ($isFtp) {
                                $rows[] = ['Mode', 'Passive', null];
                                $rows[] = ['Passive Ports', $pasvRange, null];
                                $rows[] = ['Encryption', 'Explicit FTPS (AUTH TLS) — recommended', null];
                            }
                            $rows[] = ['Username', $host->username ?: '—', 'user'];
                        }
                    @endphp
                    <div class="overflow-hidden rounded-lg ring-1 ring-slate-200 divide-y divide-slate-100 text-sm">
                        @foreach ($rows as [$label, $value, $key])
                            <div class="flex items-center justify-between gap-3 px-3 py-2.5">
                                <div class="min-w-0">
                                    <dt class="text-xs text-slate-500">{{ $label }}</dt>
                                    <dd class="font-mono text-slate-900 truncate">{{ $value }}</dd>
                                </div>
                                @if ($key)
                                    <button type="button" @click="copy({{ \Illuminate\Support\Js::from($value) }}, '{{ $key }}')"
                                        class="shrink-0 inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium text-slate-500 ring-1 ring-inset ring-slate-200 hover:bg-slate-50">
                                        <x-icon name="copy" class="w-3.5 h-3.5" />
                                        <span x-text="copied === '{{ $key }}' ? 'Copied' : 'Copy'"></span>
                                    </button>
                                @endif
                            </div>
                        @endforeach
                        {{-- Password / secret key: hidden by default, reveal + copy. --}}
                        <div class="flex items-center justify-between gap-3 px-3 py-2.5">
                            <div class="min-w-0">
                                <dt class="text-xs text-slate-500">{{ $host->ingest_protocol === 's3' ? 'Secret Key' : 'Password' }}</dt>
                                <dd class="font-mono text-slate-900 truncate">
                                    <span x-show="show">{{ $host->secret ?: '—' }}</span>
                                    <span x-show="!show" aria-hidden="true">••••••••••••</span>
                                </dd>
                            </div>
                            <div class="shrink-0 flex items-center gap-1.5">
                                <button type="button" @click="show = !show" class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium text-slate-500 ring-1 ring-inset ring-slate-200 hover:bg-slate-50">
                                    <x-icon name="eye" class="w-3.5 h-3.5" /><span x-text="show ? 'Hide' : 'Show'"></span>
                                </button>
                                <button type="button" @click="copy({{ \Illuminate\Support\Js::from((string) $host->secret) }}, 'pass')" class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium text-slate-500 ring-1 ring-inset ring-slate-200 hover:bg-slate-50">
                                    <x-icon name="copy" class="w-3.5 h-3.5" /><span x-text="copied === 'pass' ? 'Copied' : 'Copy'"></span>
                                </button>
                            </div>
                        </div>
                        {{-- Drop folder path. --}}
                        <div class="flex items-center justify-between gap-3 px-3 py-2.5">
                            <div class="min-w-0">
                                <dt class="text-xs text-slate-500">Drop Folder (on the gateway)</dt>
                                <dd class="font-mono text-slate-900 truncate">{{ $host->ingest_folder ?: '—' }}</dd>
                            </div>
                            <button type="button" @click="copy({{ \Illuminate\Support\Js::from((string) $host->ingest_folder) }}, 'folder')"
                                class="shrink-0 inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium text-slate-500 ring-1 ring-inset ring-slate-200 hover:bg-slate-50">
                                <x-icon name="copy" class="w-3.5 h-3.5" /><span x-text="copied === 'folder' ? 'Copied' : 'Copy'"></span>
                            </button>
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-slate-400">Push into the drop folder over {{ strtoupper($host->ingest_protocol) }}; the snapshot job below captures it into the repository on its schedule. Remote pushers are rooted to this folder — they cannot see the rest of the gateway.</p>
                </x-card>
            @endif

            @if ($host->connection_type === 'multiftp')
                <x-card title="FTP Accounts">
                    <x-slot:actions>
                        <form method="POST" action="{{ route('hosts.test', $host) }}">@csrf
                            <x-button size="sm" variant="secondary" icon="check">Test All</x-button>
                        </form>
                    </x-slot:actions>
                    <div class="overflow-hidden rounded-lg ring-1 ring-slate-200">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                <tr><th class="px-3 py-2 text-left font-medium">Folder</th><th class="px-3 py-2 text-left font-medium">FTP Host</th><th class="px-3 py-2 text-left font-medium">Username</th><th class="px-3 py-2 text-left font-medium">Directory</th><th class="px-3 py-2 text-right font-medium">Edit</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($host->ftp_accounts ?? [] as $i => $a)
                                    <tr class="border-t border-slate-100">
                                        <td class="px-3 py-2 font-medium text-slate-800">{{ $a['label'] ?? ($a['username'] ?? '—') }}</td>
                                        <td class="px-3 py-2 text-slate-600">{{ ($a['host'] ?? '—') . (!empty($a['port']) && $a['port'] != 21 ? ':' . $a['port'] : '') }}</td>
                                        <td class="px-3 py-2 text-slate-600">{{ $a['username'] ?? '—' }}</td>
                                        <td class="px-3 py-2 text-slate-500">{{ $a['path'] ?: '/' }}</td>
                                        <td class="px-3 py-2 text-right">
                                            <button type="button" @click="$dispatch('open-modal', 'edit-ftp-{{ $i }}')" class="inline-flex items-center gap-1 text-xs font-medium text-brand-700 hover:text-brand-800">
                                                <x-icon name="edit" class="w-3.5 h-3.5" /> Edit
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-3 py-4 text-center text-slate-400">No accounts yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-2 text-xs text-slate-400">{{ count($host->ftp_accounts ?? []) }} account(s) — each backs up into its own folder within the repository. Edit one to fix its login, then <strong>Test All</strong>.</p>
                </x-card>

                {{-- Per-account edit modal (buttons stay inside the form so it submits). --}}
                @foreach ($host->ftp_accounts ?? [] as $i => $a)
                    <x-modal name="edit-ftp-{{ $i }}" title="Edit FTP Account" icon="edit">
                        <form method="POST" action="{{ route('hosts.ftpaccount.update', [$host, $i]) }}">
                            @csrf @method('PUT')
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <x-field label="Label / Folder" for="lbl{{ $i }}"><x-input id="lbl{{ $i }}" name="label" :value="$a['label'] ?? ''" /></x-field>
                                <x-field label="Directory" for="pth{{ $i }}"><x-input id="pth{{ $i }}" name="path" :value="$a['path'] ?? ''" placeholder="/ (whole account)" /></x-field>
                                <x-field label="FTP Host" for="hst{{ $i }}" required><x-input id="hst{{ $i }}" name="host" :value="$a['host'] ?? ''" required autocomplete="off" /></x-field>
                                <x-field label="Port" for="prt{{ $i }}"><x-input id="prt{{ $i }}" name="port" :value="$a['port'] ?? '21'" /></x-field>
                                <x-field label="Username" for="usr{{ $i }}" required><x-input id="usr{{ $i }}" name="username" :value="$a['username'] ?? ''" required autocomplete="off" data-lpignore="true" data-1p-ignore /></x-field>
                                <x-field label="Password" for="pwd{{ $i }}" hint="Blank keeps the current one."><x-input id="pwd{{ $i }}" name="password" type="password" autocomplete="new-password" data-lpignore="true" data-1p-ignore /></x-field>
                            </div>
                            <div class="mt-5 flex items-center justify-end gap-2">
                                <x-button type="button" variant="secondary" @click="$dispatch('close-modal', 'edit-ftp-{{ $i }}')">Cancel</x-button>
                                <x-button type="submit" icon="check">Save Account</x-button>
                            </div>
                        </form>
                    </x-modal>
                @endforeach
            @endif

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
