<x-layouts.app title="Add Host">
    <x-page-header title="Add Host" icon="server" :subtitle="'Director: ' . $director->name" />

    <form method="POST" action="{{ route('hosts.store', $director) }}"
          x-data="{
              tab: 'basics',
              type: '{{ old('connection_type', 'agent') }}',
              auth: '{{ old('auth_type', 'key') }}',
              ingestProto: '{{ old('ingest_protocol', 'sftp') }}',
              disks: {{ \Illuminate\Support\Js::from(old('disks', [''])) }},
              accounts: {{ \Illuminate\Support\Js::from(old('ftp_accounts', [['label' => '', 'host' => '', 'port' => '21', 'username' => '', 'password' => '', 'path' => '']])) }},
              onType() {
                  if (this.type === 'multiftp' && !['basics','ftpaccts'].includes(this.tab)) this.tab = 'ftpaccts';
                  if (this.type !== 'multiftp' && this.tab === 'ftpaccts') this.tab = 'basics';
                  if (this.type === 'ingest' && !['basics','ingest'].includes(this.tab)) this.tab = 'ingest';
                  if (this.type !== 'ingest' && this.tab === 'ingest') this.tab = 'basics';
              },
          }">
        @csrf

        @if ($errors->any())
            <div class="mb-6"><x-alert type="danger" title="Please fix the highlighted fields">Some fields need attention. Check each tab.</x-alert></div>
        @endif

        <x-card>
            <nav class="flex flex-wrap items-center gap-1 pb-4 mb-5 border-b border-slate-100">
                <button type="button" @click="tab='basics'" :class="tab==='basics' ? 'bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200' : 'text-slate-500 hover:bg-slate-100'" class="px-3 py-1.5 rounded-lg text-sm font-medium transition">Basics</button>
                <button type="button" @click="tab='connection'" x-show="type !== 'agent' && type !== 'multiftp' && type !== 'ingest'" :class="tab==='connection' ? 'bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200' : 'text-slate-500 hover:bg-slate-100'" class="px-3 py-1.5 rounded-lg text-sm font-medium transition">Connection</button>
                <button type="button" @click="tab='ftpaccts'" x-show="type === 'multiftp'" :class="tab==='ftpaccts' ? 'bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200' : 'text-slate-500 hover:bg-slate-100'" class="px-3 py-1.5 rounded-lg text-sm font-medium transition">FTP Accounts</button>
                <button type="button" @click="tab='ingest'" x-show="type === 'ingest'" :class="tab==='ingest' ? 'bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200' : 'text-slate-500 hover:bg-slate-100'" class="px-3 py-1.5 rounded-lg text-sm font-medium transition">Ingest Target</button>
                <button type="button" @click="tab='disks'" x-show="type !== 'multiftp' && type !== 'ingest'" :class="tab==='disks' ? 'bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200' : 'text-slate-500 hover:bg-slate-100'" class="px-3 py-1.5 rounded-lg text-sm font-medium transition">Disks &amp; Paths</button>
                <span class="ml-auto text-xs text-slate-400" x-text="({agent:'Agent',ssh:'SSH',sftp:'SFTP',ftp:'FTP',rsync:'Rsync',s3:'S3 Compatible',multiftp:'Multi-FTP',ingest:'Ingest'})[type]"></span>
            </nav>

            {{-- Basics --}}
            <div x-show="tab==='basics'" class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <x-field label="Host Name" for="name" required :error="$errors->first('name')">
                    <x-input id="name" name="name" :value="old('name')" required autofocus placeholder="e.g. web-prod-01" />
                </x-field>
                <x-field label="Connection Type" for="connection_type" required hint="How this Director reaches the host's data.">
                    <x-select id="connection_type" name="connection_type" x-model="type" x-on:change="onType()">
                        <option value="agent">Agent (installed, outbound poll)</option>
                        <option value="ssh">SSH (rsync over SSH)</option>
                        <option value="sftp">SFTP</option>
                        <option value="ftp">FTP</option>
                        <option value="rsync">Rsync daemon</option>
                        <option value="multiftp">Multi-FTP (many accounts → one host)</option>
                        <option value="s3">S3 Compatible Bucket</option>
                        <option value="ingest">Ingest (receive — external systems push in)</option>
                    </x-select>
                </x-field>
                <x-field label="Hostname" for="hostname" hint="DNS name (optional)." :error="$errors->first('hostname')">
                    <x-input id="hostname" name="hostname" :value="old('hostname')" placeholder="host.example.com" />
                </x-field>
                <x-field label="IP Address" for="ip_address" :error="$errors->first('ip_address')">
                    <x-input id="ip_address" name="ip_address" :value="old('ip_address')" placeholder="e.g. 10.0.0.20" />
                </x-field>
                <x-field label="Default Schedule" for="default_schedule_template_id" hint="Applied to new jobs on this host.">
                    <x-select id="default_schedule_template_id" name="default_schedule_template_id">
                        <option value="">None</option>
                        @foreach ($scheduleTemplates as $st)
                            <option value="{{ $st->id }}" @selected(old('default_schedule_template_id') == $st->id)>{{ $st->name }} ({{ $st->cron }})</option>
                        @endforeach
                    </x-select>
                </x-field>
                @if ($owners->isNotEmpty())
                    <x-field label="Owner" for="owner_id" hint="User who can see and manage this host. Defaults to the director's owner." :error="$errors->first('owner_id')">
                        <x-select id="owner_id" name="owner_id">
                            <option value="">Inherit From Director</option>
                            @foreach ($owners as $owner)
                                <option value="{{ $owner->id }}" @selected(old('owner_id') == $owner->id)>{{ $owner->name }} ({{ $owner->email }})</option>
                            @endforeach
                        </x-select>
                    </x-field>
                @endif
            </div>

            {{-- Connection (agentless) --}}
            <div x-show="tab==='connection'" x-cloak>
                {{-- Push model: agent installed on the host --}}
                <template x-if="type === 'agent'">
                    <div class="space-y-4">
                        <x-alert type="info" title="Push model — the agent runs on the host">
                            You install a lightweight agent here. It dials <strong>out</strong> to the Manager (no inbound ports), backs up locally, and <strong>pushes</strong> the encrypted snapshot to the repository you choose. Pick an <strong>S3 / StorageMGR</strong> repository to keep backups offsite &mdash; a filesystem repository would store them on this host itself.
                        </x-alert>
                        <div class="rounded-xl ring-1 ring-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-900">Install the agent in 3 steps</p>
                            <ol class="mt-2 space-y-2 text-sm text-slate-600 list-decimal list-inside">
                                <li>Save this host, then on its page click <strong>Generate Enrollment Token</strong>.</li>
                                <li>Run the one-liner it shows on the host (as root):
                                    <pre class="mt-1 rounded-lg bg-slate-900 text-slate-100 text-xs p-2 overflow-x-auto"><code>curl -fsSL {{ config('app.url') }}/downloads/agent-install.sh \
  | sudo bash -s -- {{ config('app.url') }} &lt;token&gt;</code></pre>
                                </li>
                                <li>The host turns <strong>online</strong>. Create a job (source path + an <strong>S3 repository</strong> for offsite backups).</li>
                            </ol>
                        </div>
                    </div>
                </template>
                {{-- Pull model: the Manager's gateway fetches the data --}}
                <div x-show="type !== 'agent'" x-cloak class="mb-5">
                    <x-alert type="info" title="Pull model — the Manager fetches the data">
                        This Director's <strong>gateway</strong> connects to this host over <span x-text="type.toUpperCase()"></span> and pulls its data <strong>centrally</strong> &mdash; snapshots are stored on the gateway/Manager's own disk (a filesystem repository). Nothing is installed on the host, but the Manager must be able to reach it.
                    </x-alert>
                </div>
                <div x-show="type === 's3'" x-cloak class="mb-5">
                    <x-alert type="warn" title="S3-compatible source — coming soon">
                        Backing up <strong>from</strong> an S3-compatible bucket (MinIO, StorageMGR, Backblaze B2, AWS S3, …) is a planned source connector and isn't pulling yet. You can save the host now, but the gateway has no S3 source to fetch with. To <strong>receive</strong> pushed backups instead, use an <strong>Ingest</strong> connection.
                    </x-alert>
                </div>
                <div x-show="type !== 'agent'" class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <x-field label="Port" for="port" :error="$errors->first('port')">
                        <x-input id="port" name="port" type="number" :value="old('port')" x-bind:placeholder="({ssh:'22',sftp:'22',ftp:'21',rsync:'873',s3:'443'})[type] || ''" />
                    </x-field>
                    <x-field label="Username / Access Key ID" for="remote_acct" :error="$errors->first('remote_acct')">
                        <x-input id="remote_acct" name="remote_acct" :value="old('remote_acct')" autocomplete="off" data-lpignore="true" data-1p-ignore data-form-type="other" readonly onfocus="this.removeAttribute('readonly')" />
                    </x-field>
                    <template x-if="['ssh','sftp','rsync'].includes(type)">
                        <x-field label="Authentication" for="auth_type">
                            <x-select id="auth_type" name="auth_type" x-model="auth">
                                <option value="key">SSH Key</option>
                                <option value="password">Password</option>
                            </x-select>
                        </x-field>
                    </template>
                    <div x-show="type === 'ftp' || type === 's3' || (['ssh','sftp','rsync'].includes(type) && auth === 'password')">
                        <x-field label="Password / Secret Key" for="secret" :error="$errors->first('secret')">
                            <x-input id="secret" name="secret" type="password" autocomplete="new-password" data-lpignore="true" data-1p-ignore />
                        </x-field>
                    </div>
                    <div class="sm:col-span-2" x-show="['ssh','sftp','rsync'].includes(type) && auth === 'key'" x-cloak>
                        <x-field label="Private Key" for="private_key" hint="Paste the SSH private key. Stored encrypted." :error="$errors->first('private_key')">
                            <textarea id="private_key" name="private_key" rows="4" data-lpignore="true" data-1p-ignore
                                class="block w-full rounded-lg border-0 bg-white px-3 py-2 font-mono text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500"
                                placeholder="-----BEGIN OPENSSH PRIVATE KEY-----">{{ old('private_key') }}</textarea>
                        </x-field>
                    </div>
                </div>
            </div>

            {{-- FTP Accounts (multi-FTP host) --}}
            <div x-show="tab==='ftpaccts'" x-cloak>
                <x-alert type="info" title="One host, many FTP accounts" class="mb-5">
                    Add each FTP login and the directory to pull. When a job on this host runs, the Director's <strong>gateway</strong> connects to every account and backs each one into <strong>its own folder</strong> inside a single snapshot &mdash; ideal for a WHM/reseller server where you only have FTP to each cPanel account.
                </x-alert>
                @php
                    $acctInput = 'block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500';
                    $acctLabel = 'block text-xs font-medium text-slate-500 mb-1';
                @endphp
                <div class="space-y-4">
                    <template x-for="(acct, i) in accounts" :key="i">
                        <div class="rounded-xl ring-1 ring-slate-200 p-4">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-sm font-semibold text-slate-700">Account <span x-text="i+1"></span></p>
                                <button type="button" @click="accounts.splice(i,1)" x-show="accounts.length > 1" class="text-slate-400 hover:text-rose-600 p-1"><x-icon name="x" class="w-4 h-4" /></button>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="{{ $acctLabel }}">Label / Folder</label>
                                    <input type="text" :name="`ftp_accounts[${i}][label]`" x-model="acct.label" placeholder="e.g. client-site" class="{{ $acctInput }}">
                                </div>
                                <div>
                                    <label class="{{ $acctLabel }}">Directory</label>
                                    <input type="text" :name="`ftp_accounts[${i}][path]`" x-model="acct.path" placeholder="/ (whole account)" class="{{ $acctInput }}">
                                </div>
                                <div>
                                    <label class="{{ $acctLabel }}">FTP Host</label>
                                    <input type="text" :name="`ftp_accounts[${i}][host]`" x-model="acct.host" placeholder="whm.example.com or IP" autocomplete="off" class="{{ $acctInput }}">
                                </div>
                                <div>
                                    <label class="{{ $acctLabel }}">Port</label>
                                    <input type="text" :name="`ftp_accounts[${i}][port]`" x-model="acct.port" placeholder="21" class="{{ $acctInput }}">
                                </div>
                                <div>
                                    <label class="{{ $acctLabel }}">Username</label>
                                    <input type="text" :name="`ftp_accounts[${i}][username]`" x-model="acct.username" autocomplete="off" data-lpignore="true" data-1p-ignore class="{{ $acctInput }}">
                                </div>
                                <div>
                                    <label class="{{ $acctLabel }}">Password</label>
                                    <input type="password" :name="`ftp_accounts[${i}][password]`" x-model="acct.password" autocomplete="new-password" data-lpignore="true" data-1p-ignore class="{{ $acctInput }}">
                                </div>
                            </div>
                        </div>
                    </template>
                    <button type="button" @click="accounts.push({label:'',host:'',port:'21',username:'',password:'',path:''})" class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-700 hover:text-brand-800">
                        <x-icon name="plus" class="w-4 h-4" /> Add FTP Account
                    </button>
                </div>
                <p class="mt-3 text-xs text-slate-400">Tip: on one WHM/reseller server, use the same FTP Host for every account and just change the username, password, and directory.</p>
            </div>

            {{-- Ingest (receive) target --}}
            <div x-show="tab==='ingest'" x-cloak>
                <x-alert type="info" title="Receive model — external systems push in" class="mb-5">
                    This is the mirror image of a pull connector. This Director's <strong>gateway</strong> exposes a credentialed <strong>drop target</strong>; you paste its host, port, username and password into a cPanel/WHM (or appliance) <strong>SFTP backup destination</strong>, and it <strong>pushes</strong> its backups to you. A scheduled job then snapshots the drop folder into a repository &mdash; so pushed files show up under this host like any other backup.
                </x-alert>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <x-field label="Protocol" for="ingest_protocol" hint="How external systems push files in.">
                        <x-select id="ingest_protocol" name="ingest_protocol" x-model="ingestProto">
                            <option value="sftp">SFTP (recommended — available now)</option>
                            <option value="ftp">FTP (coming soon)</option>
                            <option value="s3">S3-compatible (via StorageMGR — coming soon)</option>
                        </x-select>
                    </x-field>
                    <x-field label="Listener Port" for="port" hint="Port the gateway listens on. 22 is taken by the host's sshd." :error="$errors->first('port')">
                        <x-input id="port" name="port" type="number" :value="old('port')" x-bind:placeholder="ingestProto==='ftp' ? '21' : (ingestProto==='s3' ? '9000' : '2022')" />
                    </x-field>
                    <x-field :label="'Username / Access Key'" for="remote_acct" hint="Auto-generated from the name if left blank." :error="$errors->first('remote_acct')">
                        <x-input id="remote_acct" name="remote_acct" :value="old('remote_acct')" autocomplete="off" data-lpignore="true" data-1p-ignore data-form-type="other" readonly onfocus="this.removeAttribute('readonly')" placeholder="e.g. whm_backups" />
                    </x-field>
                    <x-field label="Password / Secret Key" for="secret" hint="Leave blank to auto-generate a strong password (shown once)." :error="$errors->first('secret')">
                        <x-input id="secret" name="secret" type="password" autocomplete="new-password" data-lpignore="true" data-1p-ignore />
                    </x-field>
                    <div class="sm:col-span-2">
                        <x-field label="Drop Folder Path" for="ingest_folder" hint="On the gateway. Pushed files land here; blank uses a default under the ingest base." :error="$errors->first('ingest_folder')">
                            <x-input id="ingest_folder" name="ingest_folder" :value="old('ingest_folder')" x-bind:placeholder="'{{ rtrim(config('backup.ingest_base', '/var/backups/ingest'), '/') }}/' + ('{{ old('name') }}' || 'your-connection').toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'')" />
                        </x-field>
                    </div>
                    <div class="sm:col-span-2">
                        <x-field label="Target Repository" for="repository_id" hint="Where the drop folder is snapshotted. A default repository is created if you don't pick one.">
                            <x-select id="repository_id" name="repository_id">
                                <option value="">New default repository</option>
                                @foreach ($repositories as $repo)
                                    <option value="{{ $repo->id }}" @selected(old('repository_id') == $repo->id)>{{ $repo->name }}</option>
                                @endforeach
                            </x-select>
                        </x-field>
                    </div>
                </div>
                <div x-show="ingestProto !== 'sftp'" x-cloak class="mt-5">
                    <x-alert type="warn" title="Not receiving yet">
                        <span x-show="ingestProto === 'ftp'">FTP receive is scaffolded but not serving yet &mdash; the gateway only runs the <strong>SFTP</strong> receive server in this release. Save with SFTP to go live now; FTP lands in a follow-up.</span>
                        <span x-show="ingestProto === 's3'">S3-compatible ingest is being provided by <strong>StorageMGR</strong> (a MinIO/S3 replacement, coming soon). This option is a placeholder &mdash; use <strong>SFTP</strong> to receive backups today.</span>
                    </x-alert>
                </div>
                <p class="mt-3 text-xs text-slate-400">Set a schedule for the snapshot on the <strong>Basics</strong> tab (Default Schedule). The connection details to paste into cPanel/WHM appear on this host's page after you save.</p>
            </div>

            {{-- Disks --}}
            <div x-show="tab==='disks'" x-cloak>
                <p class="text-sm text-slate-500 mb-3">What to protect on this host. Leave empty for an agentless host to back up the whole account.</p>
                <div class="space-y-3">
                    <template x-for="(disk, i) in disks" :key="i">
                        <div class="flex items-center gap-2">
                            <input type="text" name="disks[]" x-model="disks[i]"
                                class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500"
                                x-bind:placeholder="type === 's3' ? 'bucket/prefix' : '/var/www, /etc ...'">
                            <button type="button" @click="disks.splice(i, 1)" x-show="disks.length > 1" class="text-slate-400 hover:text-rose-600 p-2 shrink-0">
                                <x-icon name="x" class="w-4 h-4" />
                            </button>
                        </div>
                    </template>
                    <button type="button" @click="disks.push('')" class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-700 hover:text-brand-800">
                        <x-icon name="plus" class="w-4 h-4" /> Add Path
                    </button>
                </div>
            </div>

            <x-slot:footer>
                <div class="flex items-center justify-between w-full">
                    <p class="text-xs text-slate-400">A default repository is created automatically.</p>
                    <div class="flex items-center gap-2">
                        <x-button variant="secondary" href="{{ route('directors.show', $director) }}">Cancel</x-button>
                        <x-button type="submit" icon="plus">Add Host</x-button>
                    </div>
                </div>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.app>
