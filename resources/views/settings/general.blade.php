<x-layouts.app title="General">
    <x-page-header title="General" icon="settings" subtitle="System-wide defaults for regional display, backups, agents, and security." />

    <form method="POST" action="{{ route('settings.general.update') }}" class="space-y-6">
        @csrf @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">

                {{-- Regional & display --}}
                <x-card title="Regional & Display" subtitle="How dates, times, and lists appear across the app.">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <x-field label="Timezone" for="timezone" required :error="$errors->first('timezone')"
                            hint="Schedules fire on this zone. Arizona is America/Phoenix.">
                            <x-select id="timezone" name="timezone">
                                @foreach ($timezones as $tz)
                                    <option value="{{ $tz }}" @selected($v['timezone'] === $tz)>{{ $tz }}</option>
                                @endforeach
                            </x-select>
                        </x-field>
                        <x-field label="Server Clock" hint="Live time in the selected zone.">
                            <x-input type="text" value="{{ $now->format('g:i A T') }}" readonly class="bg-slate-50" />
                        </x-field>
                        <x-field label="Date Format" for="date_format" :error="$errors->first('date_format')"
                            hint="How dates render in lists and details.">
                            <x-select id="date_format" name="date_format">
                                @foreach (['M j, Y' => $now->format('M j, Y'), 'Y-m-d' => $now->format('Y-m-d'), 'd/m/Y' => $now->format('d/m/Y'), 'm/d/Y' => $now->format('m/d/Y'), 'j M Y' => $now->format('j M Y'), 'l, F j, Y' => $now->format('l, F j, Y')] as $fmt => $ex)
                                    <option value="{{ $fmt }}" @selected($v['date_format'] === $fmt)>{{ $ex }}</option>
                                @endforeach
                            </x-select>
                        </x-field>
                        <x-field label="Time Format" for="time_format" :error="$errors->first('time_format')">
                            <x-select id="time_format" name="time_format">
                                <option value="g:i A" @selected($v['time_format'] === 'g:i A')>12-Hour ({{ $now->format('g:i A') }})</option>
                                <option value="H:i" @selected($v['time_format'] === 'H:i')>24-Hour ({{ $now->format('H:i') }})</option>
                            </x-select>
                        </x-field>
                        <x-field label="Week Starts On" for="week_starts_on" :error="$errors->first('week_starts_on')">
                            <x-select id="week_starts_on" name="week_starts_on">
                                <option value="sunday" @selected($v['week_starts_on'] === 'sunday')>Sunday</option>
                                <option value="monday" @selected($v['week_starts_on'] === 'monday')>Monday</option>
                            </x-select>
                        </x-field>
                        <x-field label="Rows Per Page" for="rows_per_page" :error="$errors->first('rows_per_page')"
                            hint="Pagination size for tables (10–200).">
                            <x-input type="number" id="rows_per_page" name="rows_per_page" min="10" max="200" value="{{ $v['rows_per_page'] }}" />
                        </x-field>
                    </div>
                </x-card>

                {{-- Backup defaults --}}
                <x-card title="Backup Defaults" subtitle="Prefilled when a new repository or job is created.">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <x-field label="Default Compression" for="default_compression" :error="$errors->first('default_compression')"
                            hint="Applied to new repositories.">
                            <x-select id="default_compression" name="default_compression">
                                <option value="zstd" @selected($v['default_compression'] === 'zstd')>Zstandard (recommended)</option>
                                <option value="s2" @selected($v['default_compression'] === 's2')>S2 (fastest)</option>
                                <option value="none" @selected($v['default_compression'] === 'none')>None</option>
                            </x-select>
                        </x-field>
                        <x-field label="Default Keep Latest" for="default_keep_latest" :error="$errors->first('default_keep_latest')"
                            hint="Restore points to keep on new jobs.">
                            <x-input type="number" id="default_keep_latest" name="default_keep_latest" min="1" max="1000" value="{{ $v['default_keep_latest'] }}" />
                        </x-field>
                        <x-field label="Max Concurrent Jobs" for="max_concurrent_jobs" :error="$errors->first('max_concurrent_jobs')"
                            hint="Per director, how many runs execute at once.">
                            <x-input type="number" id="max_concurrent_jobs" name="max_concurrent_jobs" min="1" max="50" value="{{ $v['max_concurrent_jobs'] }}" />
                        </x-field>
                    </div>
                    <div class="mt-5 space-y-4 border-t border-slate-100 pt-5">
                        <x-toggle name="prune_after_backup" :checked="$v['prune_after_backup'] === '1'"
                            label="Prune After Each Backup"
                            description="Apply retention and reclaim space immediately after every successful run." />
                        <x-toggle name="verify_after_backup" :checked="$v['verify_after_backup'] === '1'"
                            label="Verify After Backup"
                            description="Spot-check snapshot integrity once a run completes. Slower, safer." />
                    </div>
                </x-card>

                {{-- Agents --}}
                <x-card title="Agents" subtitle="How nodes check in and stay current.">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <x-field label="Poll Interval" for="agent_poll_interval" :error="$errors->first('agent_poll_interval')"
                            hint="Seconds between agent check-ins (5–3600).">
                            <x-input type="number" id="agent_poll_interval" name="agent_poll_interval" min="5" max="3600" value="{{ $v['agent_poll_interval'] }}" />
                        </x-field>
                        <x-field label="Mark Offline After" for="offline_after_minutes" :error="$errors->first('offline_after_minutes')"
                            hint="Minutes without a check-in before a host reads offline.">
                            <x-input type="number" id="offline_after_minutes" name="offline_after_minutes" min="1" max="1440" value="{{ $v['offline_after_minutes'] }}" />
                        </x-field>
                    </div>
                    <div class="mt-5 border-t border-slate-100 pt-5 space-y-5">
                        <x-toggle name="agent_auto_update" :checked="$v['agent_auto_update'] === '1'"
                            label="Allow Agent Auto-Update"
                            description="Agents pull and install a newer binary when the Manager advertises one, then restart." />
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <x-field label="Latest Agent Version" for="agent_latest_version" :error="$errors->first('agent_latest_version')"
                                hint="Version string agents update toward, e.g. 1.2.0.">
                                <x-input id="agent_latest_version" name="agent_latest_version" :value="$v['agent_latest_version']" placeholder="1.2.0" />
                            </x-field>
                            <x-field label="Agent Download URL" for="agent_download_url" :error="$errors->first('agent_download_url')"
                                hint="HTTPS URL the agent fetches the new binary from.">
                                <x-input id="agent_download_url" name="agent_download_url" :value="$v['agent_download_url']" placeholder="https://backup.example.com/agent/backup-agent" />
                            </x-field>
                        </div>
                    </div>
                </x-card>

                {{-- Maintenance & housekeeping --}}
                <x-card title="Maintenance & Housekeeping" subtitle="Keep the catalog and repositories tidy.">
                    <div class="mb-5">
                        <x-toggle name="auto_maintenance" :checked="$v['auto_maintenance'] === '1'"
                            label="Automatic Repository Maintenance"
                            description="Run kopia compaction and garbage collection on a cadence to reclaim space." />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 border-t border-slate-100 pt-5">
                        <x-field label="Keep Run History" for="run_history_days" :error="$errors->first('run_history_days')"
                            hint="Days of run logs. 0 = forever.">
                            <x-input type="number" id="run_history_days" name="run_history_days" min="0" max="3650" value="{{ $v['run_history_days'] }}" />
                        </x-field>
                        <x-field label="Keep Audit Log" for="audit_log_days" :error="$errors->first('audit_log_days')"
                            hint="Days of audit entries. 0 = forever.">
                            <x-input type="number" id="audit_log_days" name="audit_log_days" min="0" max="3650" value="{{ $v['audit_log_days'] }}" />
                        </x-field>
                        <x-field label="File Index Cap" for="file_index_cap" :error="$errors->first('file_index_cap')"
                            hint="Max files indexed per snapshot for browse.">
                            <x-input type="number" id="file_index_cap" name="file_index_cap" min="100" max="100000" value="{{ $v['file_index_cap'] }}" />
                        </x-field>
                    </div>
                </x-card>

                {{-- Security --}}
                <x-card title="Security" subtitle="Session and account protection defaults.">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <x-field label="Idle Session Timeout" for="session_timeout_minutes" :error="$errors->first('session_timeout_minutes')"
                            hint="Minutes before an idle session signs out.">
                            <x-input type="number" id="session_timeout_minutes" name="session_timeout_minutes" min="5" max="43200" value="{{ $v['session_timeout_minutes'] }}" />
                        </x-field>
                        <x-field label="Force Password Change" for="force_password_days" :error="$errors->first('force_password_days')"
                            hint="Days before a password must be rotated. 0 = never.">
                            <x-input type="number" id="force_password_days" name="force_password_days" min="0" max="3650" value="{{ $v['force_password_days'] }}" />
                        </x-field>
                    </div>
                    <div class="mt-5 border-t border-slate-100 pt-5">
                        <x-toggle name="require_2fa" :checked="$v['require_2fa'] === '1'"
                            label="Require Two-Factor For All Users"
                            description="Every account must set up a TOTP second factor before using the app." />
                    </div>
                </x-card>

            </div>

            {{-- Sidebar: system info --}}
            <div class="space-y-6">
                <x-card title="System">
                    <dl class="divide-y divide-slate-100 text-sm">
                        @foreach ($info as $label => $value)
                            <div class="flex items-center justify-between gap-4 py-2.5">
                                <dt class="text-slate-500 shrink-0">{{ $label }}</dt>
                                <dd class="font-medium text-slate-900 text-right truncate">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-card>
            </div>
        </div>

        <div class="flex justify-end gap-3 sticky bottom-4">
            <div class="flex gap-3 rounded-xl bg-white/90 backdrop-blur ring-1 ring-slate-200 shadow-sm px-4 py-3">
                <x-button variant="secondary" type="button" onclick="window.location.reload()">Reset</x-button>
                <x-button variant="primary" type="submit" icon="check">Save Settings</x-button>
            </div>
        </div>
    </form>
</x-layouts.app>
