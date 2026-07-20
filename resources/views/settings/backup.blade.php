@php $g = fn ($k) => \App\Models\Setting::get($k); @endphp
<x-layouts.app title="Backup & Restore">
    <x-page-header title="Backup & Restore" icon="archive" subtitle="Back up this panel's configuration and restore it later.">
        <x-slot:actions>
            <x-button variant="secondary" icon="settings" href="{{ route('settings.index') }}">Settings</x-button>
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <div class="mb-5 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-800 ring-1 ring-brand-200">{{ session('status') }}</div>
    @endif

    <div class="space-y-6">
        <x-card title="Automated Remote Backups" subtitle="Dump the database on a schedule and ship it to a remote destination.">
            <form method="POST" action="{{ route('settings.backup.schedule') }}" class="space-y-5"
                  x-data="{ t: '{{ $g('dbbackup_transport') ?: 'local' }}' }">
                @csrf @method('PUT')

                <x-toggle name="dbbackup_enabled" :checked="$g('dbbackup_enabled') === '1'" label="Enable Automated Backups" description="Run a database backup automatically and upload it to the destination below." />

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 border-t border-slate-100 pt-5">
                    <x-field label="Frequency" for="dbbackup_frequency">
                        <select id="dbbackup_frequency" name="dbbackup_frequency" class="w-full rounded-lg border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="daily" @selected(($g('dbbackup_frequency') ?: 'daily') === 'daily')>Daily</option>
                            <option value="weekly" @selected($g('dbbackup_frequency') === 'weekly')>Weekly (Mondays)</option>
                        </select>
                    </x-field>
                    <x-field label="Time" for="dbbackup_time" hint="Server time.">
                        <x-input id="dbbackup_time" name="dbbackup_time" type="time" :value="$g('dbbackup_time') ?: '02:30'" />
                    </x-field>
                    <x-field label="Keep Last" for="dbbackup_retention" hint="Older copies are pruned.">
                        <x-input id="dbbackup_retention" name="dbbackup_retention" type="number" min="1" :value="$g('dbbackup_retention') ?: 7" />
                    </x-field>
                </div>

                <x-field label="Destination" for="dbbackup_transport">
                    <select id="dbbackup_transport" name="dbbackup_transport" x-model="t" class="w-full rounded-lg border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="local">Local Directory</option>
                        <option value="s3">Amazon S3 / S3-Compatible</option>
                        <option value="storagemgr">StorageMGR</option>
                        <option value="ftp">FTP</option>
                        <option value="sftp">SFTP (SSH key)</option>
                        <option value="rsync">Rsync over SSH (SSH key)</option>
                        <option value="dropbox">Dropbox</option>
                    </select>
                </x-field>

                {{-- Local --}}
                <div x-show="t === 'local'" x-cloak class="border-t border-slate-100 pt-5">
                    <x-field label="Directory Path" for="dbbackup_local_path" hint="Absolute path on this server.">
                        <x-input id="dbbackup_local_path" name="dbbackup_local_path" :value="$g('dbbackup_local_path')" placeholder="/var/backups/panel" />
                    </x-field>
                </div>

                {{-- S3 / S3-compatible --}}
                <div x-show="t === 's3'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-5 border-t border-slate-100 pt-5">
                    <x-field label="Endpoint" for="dbbackup_s3_endpoint" hint="AWS, Backblaze B2, Wasabi, MinIO, etc." class="sm:col-span-2"><x-input id="dbbackup_s3_endpoint" name="dbbackup_s3_endpoint" :value="$g('dbbackup_s3_endpoint')" placeholder="s3.us-east-1.amazonaws.com" /></x-field>
                    <x-field label="Region" for="dbbackup_s3_region"><x-input id="dbbackup_s3_region" name="dbbackup_s3_region" :value="$g('dbbackup_s3_region') ?: 'us-east-1'" /></x-field>
                    <x-field label="Bucket" for="dbbackup_s3_bucket"><x-input id="dbbackup_s3_bucket" name="dbbackup_s3_bucket" :value="$g('dbbackup_s3_bucket')" /></x-field>
                    <x-field label="Access Key ID" for="dbbackup_s3_key"><x-input id="dbbackup_s3_key" name="dbbackup_s3_key" :value="$g('dbbackup_s3_key')" autocomplete="off" /></x-field>
                    <x-field label="Secret Key" for="dbbackup_s3_secret" hint="Leave blank to keep stored."><x-input id="dbbackup_s3_secret" name="dbbackup_s3_secret" type="password" autocomplete="new-password" data-lpignore="true" /></x-field>
                    <x-field label="Path Prefix" for="dbbackup_s3_path" class="sm:col-span-2"><x-input id="dbbackup_s3_path" name="dbbackup_s3_path" :value="$g('dbbackup_s3_path')" placeholder="panel-backups" /></x-field>
                </div>

                {{-- StorageMGR (S3-compatible, same signer) --}}
                <div x-show="t === 'storagemgr'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-5 border-t border-slate-100 pt-5">
                    <x-field label="StorageMGR Endpoint" for="dbbackup_storagemgr_endpoint" hint="Your StorageMGR instance URL." class="sm:col-span-2"><x-input id="dbbackup_storagemgr_endpoint" name="dbbackup_storagemgr_endpoint" :value="$g('dbbackup_storagemgr_endpoint')" placeholder="storage.yourdomain.com" /></x-field>
                    <x-field label="Region" for="dbbackup_storagemgr_region" hint="Default is fine for StorageMGR."><x-input id="dbbackup_storagemgr_region" name="dbbackup_storagemgr_region" :value="$g('dbbackup_storagemgr_region') ?: 'us-east-1'" /></x-field>
                    <x-field label="Bucket" for="dbbackup_storagemgr_bucket"><x-input id="dbbackup_storagemgr_bucket" name="dbbackup_storagemgr_bucket" :value="$g('dbbackup_storagemgr_bucket')" /></x-field>
                    <x-field label="Access Key" for="dbbackup_storagemgr_key"><x-input id="dbbackup_storagemgr_key" name="dbbackup_storagemgr_key" :value="$g('dbbackup_storagemgr_key')" autocomplete="off" /></x-field>
                    <x-field label="Secret Key" for="dbbackup_storagemgr_secret" hint="Leave blank to keep stored."><x-input id="dbbackup_storagemgr_secret" name="dbbackup_storagemgr_secret" type="password" autocomplete="new-password" data-lpignore="true" /></x-field>
                    <x-field label="Path Prefix" for="dbbackup_storagemgr_path" class="sm:col-span-2"><x-input id="dbbackup_storagemgr_path" name="dbbackup_storagemgr_path" :value="$g('dbbackup_storagemgr_path')" placeholder="panel-backups" /></x-field>
                </div>

                {{-- FTP --}}
                <div x-show="t === 'ftp'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-5 border-t border-slate-100 pt-5">
                    <x-field label="Host" for="dbbackup_ftp_host"><x-input id="dbbackup_ftp_host" name="dbbackup_ftp_host" :value="$g('dbbackup_ftp_host')" placeholder="ftp.example.com" /></x-field>
                    <x-field label="Port" for="dbbackup_ftp_port"><x-input id="dbbackup_ftp_port" name="dbbackup_ftp_port" type="number" :value="$g('dbbackup_ftp_port') ?: 21" /></x-field>
                    <x-field label="Username" for="dbbackup_ftp_user"><x-input id="dbbackup_ftp_user" name="dbbackup_ftp_user" :value="$g('dbbackup_ftp_user')" autocomplete="off" /></x-field>
                    <x-field label="Password" for="dbbackup_ftp_pass" hint="Leave blank to keep stored."><x-input id="dbbackup_ftp_pass" name="dbbackup_ftp_pass" type="password" autocomplete="new-password" data-lpignore="true" /></x-field>
                    <x-field label="Remote Path" for="dbbackup_ftp_path"><x-input id="dbbackup_ftp_path" name="dbbackup_ftp_path" :value="$g('dbbackup_ftp_path')" placeholder="/backups" /></x-field>
                    <div class="flex items-end"><x-toggle name="dbbackup_ftp_passive" :checked="$g('dbbackup_ftp_passive') !== '0'" label="Passive Mode" /></div>
                </div>

                {{-- SFTP --}}
                <div x-show="t === 'sftp'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-5 border-t border-slate-100 pt-5">
                    <x-field label="Host" for="dbbackup_sftp_host"><x-input id="dbbackup_sftp_host" name="dbbackup_sftp_host" :value="$g('dbbackup_sftp_host')" /></x-field>
                    <x-field label="Port" for="dbbackup_sftp_port"><x-input id="dbbackup_sftp_port" name="dbbackup_sftp_port" type="number" :value="$g('dbbackup_sftp_port') ?: 22" /></x-field>
                    <x-field label="Username" for="dbbackup_sftp_user"><x-input id="dbbackup_sftp_user" name="dbbackup_sftp_user" :value="$g('dbbackup_sftp_user')" autocomplete="off" /></x-field>
                    <x-field label="Remote Path" for="dbbackup_sftp_path"><x-input id="dbbackup_sftp_path" name="dbbackup_sftp_path" :value="$g('dbbackup_sftp_path')" placeholder="/home/user/backups" /></x-field>
                    <x-field label="Private Key" for="dbbackup_sftp_key" hint="Unencrypted OpenSSH key. Leave blank to keep stored." class="sm:col-span-2">
                        <textarea id="dbbackup_sftp_key" name="dbbackup_sftp_key" rows="4" autocomplete="off" placeholder="-----BEGIN OPENSSH PRIVATE KEY-----" class="w-full rounded-lg border-slate-300 font-mono text-xs focus:border-brand-500 focus:ring-brand-500"></textarea>
                    </x-field>
                </div>

                {{-- Rsync --}}
                <div x-show="t === 'rsync'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-5 border-t border-slate-100 pt-5">
                    <x-field label="Host" for="dbbackup_rsync_host"><x-input id="dbbackup_rsync_host" name="dbbackup_rsync_host" :value="$g('dbbackup_rsync_host')" /></x-field>
                    <x-field label="Port" for="dbbackup_rsync_port"><x-input id="dbbackup_rsync_port" name="dbbackup_rsync_port" type="number" :value="$g('dbbackup_rsync_port') ?: 22" /></x-field>
                    <x-field label="Username" for="dbbackup_rsync_user"><x-input id="dbbackup_rsync_user" name="dbbackup_rsync_user" :value="$g('dbbackup_rsync_user')" autocomplete="off" /></x-field>
                    <x-field label="Remote Path" for="dbbackup_rsync_path"><x-input id="dbbackup_rsync_path" name="dbbackup_rsync_path" :value="$g('dbbackup_rsync_path')" placeholder="/srv/backups" /></x-field>
                    <x-field label="Private Key" for="dbbackup_rsync_key" hint="Unencrypted OpenSSH key. Leave blank to keep stored." class="sm:col-span-2">
                        <textarea id="dbbackup_rsync_key" name="dbbackup_rsync_key" rows="4" autocomplete="off" placeholder="-----BEGIN OPENSSH PRIVATE KEY-----" class="w-full rounded-lg border-slate-300 font-mono text-xs focus:border-brand-500 focus:ring-brand-500"></textarea>
                    </x-field>
                </div>

                {{-- Dropbox --}}
                <div x-show="t === 'dropbox'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-5 border-t border-slate-100 pt-5">
                    <x-field label="Access Token" for="dbbackup_dropbox_token" hint="Leave blank to keep stored."><x-input id="dbbackup_dropbox_token" name="dbbackup_dropbox_token" type="password" autocomplete="new-password" data-lpignore="true" /></x-field>
                    <x-field label="Folder Path" for="dbbackup_dropbox_path"><x-input id="dbbackup_dropbox_path" name="dbbackup_dropbox_path" :value="$g('dbbackup_dropbox_path')" placeholder="/panel-backups" /></x-field>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-5">
                    <p class="text-sm {{ str_starts_with((string) $g('dbbackup_last_result'), 'error') ? 'text-rose-600' : 'text-slate-500' }}">
                        {{ $g('dbbackup_last_result') ? $g('dbbackup_last_result') : 'No backup run yet.' }}
                    </p>
                    <div class="flex items-center gap-2">
                        <x-button type="submit" variant="secondary" icon="check">Save</x-button>
                        <x-button type="button" icon="download" x-on:click="document.getElementById('run-backup-now').submit()">Run Backup Now</x-button>
                    </div>
                </div>
            </form>
            <form method="POST" action="{{ route('settings.backup.run') }}" id="run-backup-now" class="hidden">@csrf</form>
        </x-card>

        <x-card title="Configuration Backup" subtitle="A JSON snapshot of every panel setting: branding, notifications, integrations, license, and more.">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-slate-500">
                    @if ($g('last_config_backup_at'))
                        Last downloaded {{ \Illuminate\Support\Carbon::parse($g('last_config_backup_at'))->diffForHumans() }}.
                    @else
                        No configuration backup downloaded yet.
                    @endif
                </p>
                <x-button icon="download" href="{{ route('settings.backup.config') }}">Download Config</x-button>
            </div>
        </x-card>

        <x-card title="Full Database Snapshot" subtitle="A complete restore point of the entire panel database.">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-slate-500">Downloads a compressed SQL dump. Keep it somewhere safe; restore it with your database tools for a full rebuild.</p>
                <x-button variant="secondary" icon="download" href="{{ route('settings.backup.database') }}">Download Database</x-button>
            </div>
        </x-card>

        <x-card title="Restore Configuration" subtitle="Upload a configuration backup to re-apply its settings to this panel.">
            <form method="POST" action="{{ route('settings.backup.restore') }}" enctype="multipart/form-data"
                  x-data="{ confirming: false }" x-on:submit="if (! confirming) { $event.preventDefault(); confirming = true; }">
                @csrf
                <div class="mb-4 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-200">
                    Restoring overwrites current settings with the values in the file. Download a fresh backup first.
                </div>
                <x-field label="Backup File" for="backup" hint="A .json configuration backup exported from this panel.">
                    <input type="file" id="backup" name="backup" accept="application/json,.json" required
                        class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200">
                </x-field>
                <div class="mt-4 flex items-center gap-2">
                    <template x-if="! confirming"><x-button type="submit" variant="secondary" icon="refresh">Restore Configuration</x-button></template>
                    <template x-if="confirming">
                        <span class="flex flex-wrap items-center gap-2">
                            <span class="text-sm text-amber-800">Overwrite current settings with this file?</span>
                            <x-button type="button" variant="secondary" size="sm" x-on:click="confirming = false">Cancel</x-button>
                            <x-button type="submit" variant="danger" size="sm" icon="check">Confirm Restore</x-button>
                        </span>
                    </template>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.app>
