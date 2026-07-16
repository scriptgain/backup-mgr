@php
    $cards = [
        ['General', 'Regional display, backup defaults, agents, and security.', 'settings', route('settings.general.edit'), true],
        ['Repositories', 'Storage targets (S3 / filesystem) with encryption + compression.', 'cloud', route('repositories.index'), true],
        ['Storage & Disks', 'Detected disks and usage across your directors.', 'cloud', route('settings.storage.index'), true],
        ['Schedule Templates', 'Prebuilt schedules to assign to hosts and jobs.', 'clock', route('schedule-templates.index'), true],
        ['Notifications', 'Email alerts when a backup fails.', 'bell', route('settings.notifications.edit'), true],
        ['Branding', 'Product name, tagline, and accent color.', 'edit', route('settings.branding.edit'), true],
        ['API Tokens', 'Create and revoke full-access API tokens.', 'key', route('settings.tokens.index'), true],
        ['Password', 'Change your account password.', 'lock', route('settings.password.edit'), true],
        ['Two-Factor Auth', 'Add a TOTP second factor to your login.', 'shield', route('settings.2fa.show'), true],
        ['License', 'Enter your key, sync entitlement, and manage your subscription.', 'shield', route('settings.license.edit'), true],
        ['Maintenance', 'Repository pruning and kopia maintenance windows.', 'refresh', route('settings.maintenance.edit'), true],
    ];
    if (auth()->user()->isAdmin()) {
        $cards[] = ['Host & SSL', 'Set the hostname and issue an SSL certificate.', 'globe', route('settings.host.edit'), true];
        $cards[] = ['Firewall', 'Sessions, IP bans, allowlist, and login protection.', 'shield', route('settings.firewall.index'), true];
        $cards[] = ['Users & Admins', 'Manage users, roles, and their hosts.', 'users', route('settings.users.index'), true];
        $cards[] = ['Audit Log', 'Who signed in and changed what, across the fleet.', 'book', route('settings.audit.index'), true];
    }
@endphp
<x-layouts.app title="Settings">
    <x-page-header title="Settings" icon="settings" subtitle="Configure repositories, schedules, tokens, and branding." />

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach ($cards as [$title, $desc, $icon, $href, $ready])
            <a href="{{ $href }}" class="group bg-white rounded-xl ring-1 ring-slate-200 shadow-sm p-5 transition hover:ring-brand-300 hover:shadow {{ $ready ? '' : 'opacity-60 pointer-events-none' }}">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-brand-50 text-brand-600 ring-1 ring-brand-100">
                        <x-icon :name="$icon" class="w-5 h-5" />
                    </span>
                    <h3 class="text-[15px] font-semibold text-slate-900">{{ $title }}</h3>
                    @unless ($ready)<x-badge color="neutral" class="ml-auto">Soon</x-badge>@endunless
                </div>
                <p class="mt-3 text-sm text-slate-500">{{ $desc }}</p>
            </a>
        @endforeach
    </div>
</x-layouts.app>
