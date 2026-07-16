<x-layouts.app title="Add Host">
    <x-page-header title="Add Host" icon="server" :subtitle="'Director: ' . $director->name" />

    <form method="POST" action="{{ route('hosts.store', $director) }}"
          x-data="{
              tab: 'basics',
              type: '{{ old('connection_type', 'agent') }}',
              auth: '{{ old('auth_type', 'key') }}',
              disks: {{ \Illuminate\Support\Js::from(old('disks', [''])) }},
          }">
        @csrf

        @if ($errors->any())
            <div class="mb-6"><x-alert type="danger" title="Please fix the highlighted fields">Some fields need attention. Check each tab.</x-alert></div>
        @endif

        <x-card>
            <nav class="flex flex-wrap items-center gap-1 pb-4 mb-5 border-b border-slate-100">
                <button type="button" @click="tab='basics'" :class="tab==='basics' ? 'bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200' : 'text-slate-500 hover:bg-slate-100'" class="px-3 py-1.5 rounded-lg text-sm font-medium transition">Basics</button>
                <button type="button" @click="tab='connection'" x-show="type !== 'agent'" :class="tab==='connection' ? 'bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200' : 'text-slate-500 hover:bg-slate-100'" class="px-3 py-1.5 rounded-lg text-sm font-medium transition">Connection</button>
                <button type="button" @click="tab='disks'" :class="tab==='disks' ? 'bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200' : 'text-slate-500 hover:bg-slate-100'" class="px-3 py-1.5 rounded-lg text-sm font-medium transition">Disks &amp; Paths</button>
                <span class="ml-auto text-xs text-slate-400" x-text="({agent:'Agent',ssh:'SSH',sftp:'SFTP',ftp:'FTP',rsync:'Rsync',s3:'S3'})[type]"></span>
            </nav>

            {{-- Basics --}}
            <div x-show="tab==='basics'" class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <x-field label="Host Name" for="name" required :error="$errors->first('name')">
                    <x-input id="name" name="name" :value="old('name')" required autofocus placeholder="e.g. web-prod-01" />
                </x-field>
                <x-field label="Connection Type" for="connection_type" required hint="How this Director reaches the host's data.">
                    <x-select id="connection_type" name="connection_type" x-model="type">
                        <option value="agent">Agent (installed, outbound poll)</option>
                        <option value="ssh">SSH (rsync over SSH)</option>
                        <option value="sftp">SFTP</option>
                        <option value="ftp">FTP</option>
                        <option value="rsync">Rsync daemon</option>
                        <option value="s3">S3 bucket</option>
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
                <template x-if="type === 'agent'">
                    <x-alert type="info" title="Agent Connector">After creating this host, install the agent and enroll it. It dials out only.</x-alert>
                </template>
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
