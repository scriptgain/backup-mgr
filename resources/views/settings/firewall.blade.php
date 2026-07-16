@php
    use Carbon\Carbon;
    $shortUa = function (?string $ua) {
        if (! $ua) return 'Unknown';
        return \Illuminate\Support\Str::limit($ua, 60);
    };
@endphp
<x-layouts.app title="Firewall">
    <x-page-header title="Firewall" icon="shield" subtitle="Sessions, IP bans, access allowlist, and failed-login protection.">
        <x-slot:actions>
            <x-button variant="secondary" icon="settings" href="{{ route('settings.index') }}">Settings</x-button>
        </x-slot:actions>
    </x-page-header>

    @if (session('error'))
        <div class="mb-6"><x-alert type="danger">{{ session('error') }}</x-alert></div>
    @endif

    <div class="space-y-8">

        {{-- 1. Active Sessions -------------------------------------------- --}}
        <x-card title="Active Sessions" subtitle="Signed-in sessions stored in the database. Revoke to force a re-login." flush>
            @if ($sessions->isEmpty())
                <x-empty-state icon="users" title="No Active Sessions" description="Sessions appear here as users sign in." />
            @else
                <x-table flush>
                    <thead>
                        <tr>
                            <th>User</th><th>IP Address</th><th>Browser</th><th>Last Active</th><th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sessions as $s)
                            @php $isCurrent = $s->id === $currentSessionId; @endphp
                            <tr>
                                <td>
                                    @if ($s->user_name)
                                        <span class="font-medium text-slate-900">{{ $s->user_name }}</span>
                                        <span class="block text-xs text-slate-500">{{ $s->user_email }}</span>
                                    @else
                                        <span class="text-slate-500">Guest</span>
                                    @endif
                                </td>
                                <td class="font-mono text-xs">{{ $s->ip_address ?: '—' }}</td>
                                <td class="text-slate-500">{{ $shortUa($s->user_agent) }}</td>
                                <td class="whitespace-nowrap">{{ Carbon::createFromTimestamp($s->last_activity)->diffForHumans() }}</td>
                                <td class="text-right">
                                    @if ($isCurrent)
                                        <x-badge color="info">Current</x-badge>
                                    @else
                                        <x-confirm-action
                                            name="revoke-session-{{ $loop->index }}"
                                            :action="route('settings.firewall.session.revoke', ['id' => $s->id])"
                                            method="DELETE"
                                            tone="danger"
                                            title="Revoke Session?"
                                            message="This signs the user out and forces them to log in again."
                                            confirm="Revoke"
                                            confirmVariant="danger"
                                            confirmIcon="x-circle">
                                            <x-button variant="secondary" size="sm" icon="x-circle">Revoke</x-button>
                                        </x-confirm-action>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </x-card>

        {{-- 2. IP Bans ---------------------------------------------------- --}}
        <x-card title="IP Bans" subtitle="A banned IP receives a 403 on every request. Expired bans no longer apply.">
            <form method="POST" action="{{ route('settings.firewall.ban') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                @csrf
                <x-field label="IP Address" for="ban_ip" :error="$errors->first('ip')">
                    <x-input id="ban_ip" name="ip" placeholder="203.0.113.10" :value="old('ip')" />
                </x-field>
                <x-field label="Reason" for="ban_reason" :error="$errors->first('reason')">
                    <x-input id="ban_reason" name="reason" placeholder="Optional" :value="old('reason')" />
                </x-field>
                <x-field label="Expires At" for="ban_expires" hint="Blank = permanent." :error="$errors->first('expires_at')">
                    <x-input id="ban_expires" name="expires_at" type="datetime-local" :value="old('expires_at')" />
                </x-field>
                <div>
                    <x-button type="submit" icon="shield" class="w-full">Ban IP</x-button>
                </div>
            </form>

            <div class="mt-6"
                x-data="{
                    selected: [],
                    confirming: false,
                    allIds: [{{ $bans->pluck('id')->implode(',') }}],
                    toggleAll(e) { this.selected = e.target.checked ? [...this.allIds] : []; this.confirming = false; },
                    submitBulk() {
                        const f = this.$refs.bulkForm;
                        f.querySelectorAll('input.js-dyn').forEach(n => n.remove());
                        this.selected.forEach(id => {
                            const i = document.createElement('input');
                            i.type = 'hidden'; i.name = 'ids[]'; i.value = id; i.className = 'js-dyn';
                            f.appendChild(i);
                        });
                        f.submit();
                    }
                }">
                @if ($bans->isEmpty())
                    <x-empty-state icon="shield-check" title="No Banned IPs" description="Manually ban an address above, or let failed-login protection do it automatically." />
                @else
                    {{-- Hidden form the bulk action posts through. --}}
                    <form method="POST" action="{{ route('settings.firewall.bulk') }}" x-ref="bulkForm" class="hidden">
                        @csrf
                        <input type="hidden" name="action" value="delete">
                    </form>

                    {{-- Bulk actions bar: appears once at least one ban is selected. --}}
                    <div x-show="selected.length" x-cloak class="mb-3 flex flex-wrap items-center justify-between gap-3 rounded-lg bg-brand-50 px-4 py-2.5 ring-1 ring-inset ring-brand-200">
                        <span class="text-sm font-medium text-brand-800"><span x-text="selected.length"></span> selected</span>
                        <div class="flex items-center gap-2">
                            <template x-if="! confirming">
                                <x-button type="button" variant="danger" size="sm" icon="trash" x-on:click="confirming = true">Delete Selected</x-button>
                            </template>
                            <template x-if="confirming">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm text-brand-800">Remove <span x-text="selected.length"></span> ban(s)?</span>
                                    <x-button type="button" variant="secondary" size="sm" x-on:click="confirming = false">Cancel</x-button>
                                    <x-button type="button" variant="danger" size="sm" icon="trash" x-on:click="submitBulk()">Confirm Delete</x-button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <x-table>
                        <thead>
                            <tr>
                                <th class="w-10">
                                    <input type="checkbox" x-on:change="toggleAll($event)"
                                        :checked="selected.length > 0 && selected.length === allIds.length"
                                        class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500 align-middle" aria-label="Select all bans">
                                </th>
                                <th>IP Address</th><th>Reason</th><th>Status</th><th>Banned By</th><th>When</th><th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bans as $ban)
                                <tr>
                                    <td>
                                        <input type="checkbox" x-model.number="selected" value="{{ $ban->id }}" x-on:change="confirming = false"
                                            class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500 align-middle" aria-label="Select ban {{ $ban->ip }}">
                                    </td>
                                    <td class="font-mono text-xs">{{ $ban->ip }}</td>
                                    <td class="text-slate-500">{{ $ban->reason ?: '—' }}</td>
                                    <td>
                                        @if ($ban->isExpired())
                                            <x-badge color="neutral">Expired</x-badge>
                                        @elseif ($ban->expires_at)
                                            <x-badge color="warn">Until {{ $ban->expires_at->format('M j, H:i') }}</x-badge>
                                        @else
                                            <x-badge color="danger">Permanent</x-badge>
                                        @endif
                                    </td>
                                    <td class="text-slate-500">{{ $ban->creator?->name ?? 'Automatic' }}</td>
                                    <td class="whitespace-nowrap text-slate-500">{{ $ban->created_at?->diffForHumans() }}</td>
                                    <td class="text-right">
                                        <x-confirm-action
                                            name="unban-{{ $ban->id }}"
                                            :action="route('settings.firewall.unban', $ban)"
                                            method="DELETE"
                                            title="Unban This IP?"
                                            message="This address will be able to reach the app again."
                                            confirm="Unban"
                                            confirmIcon="check">
                                            <x-button variant="secondary" size="sm" icon="check">Unban</x-button>
                                        </x-confirm-action>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-table>
                @endif
            </div>
        </x-card>

        {{-- Combined settings form: access limit + failed-login protection -- --}}
        <form method="POST" action="{{ route('settings.firewall.update') }}" class="space-y-8">
            @csrf
            @method('PUT')

            {{-- 3. Access Limit (allowlist) ------------------------------- --}}
            <x-card title="Access Limit" subtitle="When on, only the listed IPs and CIDR ranges can reach this app. Everything else gets a 403.">
                <div class="space-y-5">
                    <x-alert type="warn" title="Handle With Care">
                        Turning this on locks out every address not on the list. Your current IP
                        (<span class="font-mono">{{ $currentIp }}</span>) is added automatically so you keep access.
                        If you ever lock yourself out, run <span class="font-mono">php artisan firewall:clear</span> over SSH.
                    </x-alert>

                    <x-toggle name="access_limit_enabled" :checked="$settings['access_limit_enabled']"
                        label="Enable Access Limit"
                        description="Restrict the app to the allowlist below." />

                    <x-field label="Allowed IPs And Ranges" for="ip_allowlist"
                        hint="One per line. Plain IPs or CIDR (e.g. 203.0.113.0/24)."
                        :error="$errors->first('ip_allowlist')">
                        <textarea id="ip_allowlist" name="ip_allowlist" rows="5"
                            class="block w-full rounded-lg border-0 bg-white px-3 py-2 font-mono text-sm text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500"
                            placeholder="203.0.113.10&#10;198.51.100.0/24">{{ old('ip_allowlist', $settings['ip_allowlist']) }}</textarea>
                    </x-field>
                </div>
            </x-card>

            {{-- 4. Failed-Login Auto-Ban ----------------------------------- --}}
            <x-card title="Failed-Login Protection" subtitle="Automatically ban an IP after too many failed sign-ins within the window.">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <x-field label="Failed Login Limit" for="failed_login_limit"
                        hint="Auto-ban after this many failures." :error="$errors->first('failed_login_limit')">
                        <x-input id="failed_login_limit" name="failed_login_limit" type="number" min="1"
                            :value="old('failed_login_limit', $settings['failed_login_limit'])" />
                    </x-field>
                    <x-field label="Lockout Minutes" for="lockout_minutes"
                        hint="Counting window and auto-ban duration." :error="$errors->first('lockout_minutes')">
                        <x-input id="lockout_minutes" name="lockout_minutes" type="number" min="1"
                            :value="old('lockout_minutes', $settings['lockout_minutes'])" />
                    </x-field>
                </div>
            </x-card>

            <div class="flex items-center justify-end gap-2">
                <x-button variant="secondary" href="{{ route('settings.index') }}">Cancel</x-button>
                <x-button type="submit" icon="check">Save Settings</x-button>
            </div>
        </form>

        {{-- Recent failed attempts (read-only) ---------------------------- --}}
        <x-card title="Recent Failed Attempts"
            subtitle="Failed sign-ins per IP within the last {{ $settings['lockout_minutes'] }} minutes." flush>
            @if ($attempts->isEmpty())
                <x-empty-state icon="shield-check" title="No Recent Failures" description="Failed sign-in attempts within the window appear here." />
            @else
                <x-table flush>
                    <thead>
                        <tr>
                            <th>IP Address</th><th>Last Email Tried</th><th>Failures</th><th>Last Attempt</th><th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($attempts as $a)
                            <tr>
                                <td class="font-mono text-xs">{{ $a->ip }}</td>
                                <td class="text-slate-500">{{ $a->email ?: '—' }}</td>
                                <td>
                                    <x-badge :color="$a->attempts >= $settings['failed_login_limit'] ? 'danger' : 'warn'">
                                        {{ $a->attempts }}
                                    </x-badge>
                                </td>
                                <td class="whitespace-nowrap text-slate-500">{{ Carbon::parse($a->last_attempt)->diffForHumans() }}</td>
                                <td class="text-right">
                                    @if ($a->ip !== $currentIp)
                                        <x-button variant="secondary" size="sm" icon="shield"
                                            x-data @click="$dispatch('open-modal', 'ban-attempt-{{ $loop->index }}')">Ban</x-button>
                                        <x-modal name="ban-attempt-{{ $loop->index }}" title="Ban This IP?" icon="warning" tone="danger" maxWidth="max-w-md">
                                            This address will receive a 403 on every request until unbanned.
                                            <x-slot:footer>
                                                <x-button variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'ban-attempt-{{ $loop->index }}')">Cancel</x-button>
                                                <form method="POST" action="{{ route('settings.firewall.ban') }}">
                                                    @csrf
                                                    <input type="hidden" name="ip" value="{{ $a->ip }}">
                                                    <input type="hidden" name="reason" value="Banned from failed login attempts">
                                                    <x-button variant="danger" size="sm" type="submit" icon="shield">Ban IP</x-button>
                                                </form>
                                            </x-slot:footer>
                                        </x-modal>
                                    @else
                                        <x-badge color="info">Your IP</x-badge>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </x-card>

    </div>
</x-layouts.app>
