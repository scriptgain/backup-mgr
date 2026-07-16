<x-layouts.app title="Design System">
    <x-page-header title="Design System" icon="dashboard" subtitle="Vault Cyan — reusable Blade + Tailwind components." />

    <div class="space-y-10">

        {{-- Buttons --}}
        <section>
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-3">Buttons</h2>
            <x-card>
                <div class="flex flex-wrap items-center gap-3">
                    <x-button>Primary</x-button>
                    <x-button variant="secondary">Secondary</x-button>
                    <x-button variant="ghost">Ghost</x-button>
                    <x-button variant="danger">Danger</x-button>
                    <x-button icon="plus">With Icon</x-button>
                    <x-button size="sm">Small</x-button>
                    <x-button size="lg">Large</x-button>
                    <x-button disabled>Disabled</x-button>
                </div>
            </x-card>
        </section>

        {{-- Badges --}}
        <section>
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-3">Status Badges</h2>
            <x-card>
                <div class="flex flex-wrap items-center gap-3">
                    <x-badge color="success" dot>Success</x-badge>
                    <x-badge color="info" dot>Running</x-badge>
                    <x-badge color="warn" dot>Warnings</x-badge>
                    <x-badge color="danger" dot>Failed</x-badge>
                    <x-badge color="neutral">Disabled</x-badge>
                </div>
            </x-card>
        </section>

        {{-- Toggles --}}
        <section>
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-3">Toggles</h2>
            <x-card>
                <div class="space-y-4 max-w-md">
                    <x-toggle :checked="true" label="Enable Job" description="Run this backup on its schedule." />
                    <x-toggle label="SMS Alerts on Failure" description="Notify when a run fails." />
                </div>
            </x-card>
        </section>

        {{-- Alerts --}}
        <section>
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-3">Alerts</h2>
            <div class="space-y-3">
                <x-alert type="info" title="Heads Up">Agents poll the master over outbound HTTPS only.</x-alert>
                <x-alert type="success" title="Backup Complete">web-prod-01 snapshot finished in 38 seconds.</x-alert>
                <x-alert type="warn" title="Retention Warning">This policy keeps only 3 daily restore points.</x-alert>
                <x-alert type="danger" title="Run Failed">Could not reach the S3 endpoint. Check credentials.</x-alert>
            </div>
        </section>

        {{-- Form controls --}}
        <section>
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-3">Form Controls</h2>
            <x-card>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 max-w-2xl">
                    <x-field label="Job Name" for="jn" required hint="A short, human label.">
                        <x-input id="jn" placeholder="e.g. Site Roots" />
                    </x-field>
                    <x-field label="Connector" for="conn">
                        <x-select id="conn">
                            <option>Agent (Local)</option>
                            <option>SFTP</option>
                            <option>Rsync over SSH</option>
                            <option>FTP</option>
                            <option>S3 Bucket</option>
                        </x-select>
                    </x-field>
                    <x-field label="Bucket" for="bk" error="This field is required.">
                        <x-input id="bk" placeholder="backup-backups" />
                    </x-field>
                    <x-field label="Compression" for="cmp">
                        <x-select id="cmp">
                            <option>zstd</option>
                            <option>gzip</option>
                            <option>none</option>
                        </x-select>
                    </x-field>
                </div>
            </x-card>
        </section>

        {{-- Table --}}
        <section>
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-3">Table</h2>
            <x-table>
                <thead>
                    <tr><th>Host</th><th>Connector</th><th>Status</th><th class="text-right">Last Seen</th></tr>
                </thead>
                <tbody>
                    <tr><td class="font-medium text-slate-900">web-prod-01</td><td>Agent</td><td><x-badge color="success" dot>Online</x-badge></td><td class="text-right text-slate-500">12s ago</td></tr>
                    <tr><td class="font-medium text-slate-900">legacy-ftp</td><td>FTP</td><td><x-badge color="warn" dot>Stale</x-badge></td><td class="text-right text-slate-500">2 days ago</td></tr>
                    <tr><td class="font-medium text-slate-900">db-prod-01</td><td>Agent</td><td><x-badge color="success" dot>Online</x-badge></td><td class="text-right text-slate-500">1m ago</td></tr>
                </tbody>
            </x-table>
        </section>

        {{-- Dropdown + Modal --}}
        <section>
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-3">Menus &amp; Modals</h2>
            <x-card>
                <div class="flex flex-wrap items-center gap-4">
                    <x-dropdown align="left">
                        <x-slot:trigger>
                            <x-button variant="secondary" size="sm" icon="dots">Actions</x-button>
                        </x-slot:trigger>
                        <x-dropdown-item icon="play">Run Now</x-dropdown-item>
                        <x-dropdown-item icon="settings">Edit</x-dropdown-item>
                        <x-dropdown-item icon="trash" danger>Delete</x-dropdown-item>
                    </x-dropdown>

                    <x-button variant="danger" size="sm" icon="trash" x-data @click="$dispatch('open-modal', 'demo-confirm')">Delete Job</x-button>
                </div>
            </x-card>

            <x-modal name="demo-confirm" title="Delete Backup Job?">
                This deletes the job, its schedule, and its run history. Data already written to the repository is not removed.
                <x-slot:footer>
                    <x-button variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'demo-confirm')">Cancel</x-button>
                    <x-button variant="danger" size="sm" icon="trash">Delete Job</x-button>
                </x-slot:footer>
            </x-modal>
        </section>

        {{-- Empty state --}}
        <section>
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-3">Empty State</h2>
            <x-card>
                <x-empty-state icon="archive" title="No Snapshots Yet" description="Run a backup job to create your first restore point.">
                    <x-slot:action><x-button icon="play">Run a Backup</x-button></x-slot:action>
                </x-empty-state>
            </x-card>
        </section>

    </div>
</x-layouts.app>
