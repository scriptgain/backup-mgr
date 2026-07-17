@php
    use Illuminate\Support\Facades\Route as RouteFacade;
    $isAdmin = auth()->check() && auth()->user()->isAdmin();
    // Logically grouped + ordered. Storage (BackupMGR) and Email (-MGR panels) both
    // listed; each renders only where its route exists, so one component fits the
    // whole fleet. [label, icon, route, active-pattern].
    $groups = [
        ['Preferences', [
            ['General', 'settings', 'settings.general.edit', 'settings.general.*'],
            ['Branding', 'edit', 'settings.branding.edit', 'settings.branding.*'],
            ['Notifications', 'bell', 'settings.notifications.edit', 'settings.notifications.*'],
            ['Storage', 'cloud', 'settings.storage.index', 'settings.storage.*'],
            ['Email', 'envelope', 'settings.email.edit', 'settings.email.*'],
        ]],
        ['Security', [
            ['Password', 'lock', 'settings.password.edit', 'settings.password.*'],
            ['Two-Factor', 'shield', 'settings.2fa.show', 'settings.2fa.*'],
            ['API Tokens', 'key', 'settings.tokens.index', 'settings.tokens.*'],
        ]],
        ['System', [
            ['License', 'shield', 'settings.license.edit', 'settings.license.*'],
            ['Updates', 'download', 'settings.updates.show', 'settings.updates.*'],
            ['Maintenance', 'refresh', 'settings.maintenance.edit', 'settings.maintenance.*'],
        ]],
    ];
    if ($isAdmin) {
        $groups[] = ['Administration', [
            ['Host & SSL', 'globe', 'settings.host.edit', 'settings.host.*'],
            ['Firewall', 'shield', 'settings.firewall.index', 'settings.firewall.*'],
            ['Users', 'users', 'settings.users.index', 'settings.users.*'],
            ['Audit', 'book', 'settings.audit.index', 'settings.audit.*'],
        ]];
    }
    $groups = array_values(array_filter(array_map(function ($g) {
        [$title, $items] = $g;
        $items = array_values(array_filter($items, fn ($t) => RouteFacade::has($t[2])));
        return $items ? [$title, $items] : null;
    }, $groups)));
@endphp
@if (count($groups))
    {{-- Plain CSS (not Tailwind) so the purged build can't strip it. Vertical
         grouped menu; the layout places this in a sticky left column. --}}
    <style>
        .settings-shell{display:grid;grid-template-columns:230px minmax(0,1fr);gap:1.5rem;align-items:start;}
        .settings-aside{position:sticky;top:5rem;}
        @media (max-width:768px){.settings-shell{grid-template-columns:1fr;}.settings-aside{position:static;}}
        .st-menu{display:flex;flex-direction:column;gap:.15rem;background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:.5rem;box-shadow:0 1px 2px rgba(0,0,0,.05);}
        .st-group{font-size:.6875rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;padding:.65rem .6rem .25rem;}
        .st-group:first-child{padding-top:.25rem;}
        .st-item{display:flex;align-items:center;gap:.6rem;padding:.5rem .6rem;border-radius:.55rem;font-size:.875rem;font-weight:500;color:#475569;text-decoration:none;transition:background .15s,color .15s;}
        .st-item:hover{background:#f1f5f9;color:#0f172a;}
        .st-item.is-active{background:#1e293b;color:#fff;font-weight:600;}
        .st-item svg{width:1.05rem;height:1.05rem;flex:0 0 auto;}
    </style>
    <nav class="st-menu" aria-label="Settings sections">
        @foreach ($groups as [$groupTitle, $items])
            <p class="st-group">{{ $groupTitle }}</p>
            @foreach ($items as [$label, $icon, $routeName, $pattern])
                @php $active = request()->routeIs($pattern); @endphp
                <a href="{{ route($routeName) }}" class="st-item {{ $active ? 'is-active' : '' }}" @if ($active) aria-current="page" @endif>
                    <x-icon :name="$icon" />
                    <span>{{ $label }}</span>
                </a>
            @endforeach
        @endforeach
    </nav>
@endif
