@php
    use Illuminate\Support\Facades\Route as RouteFacade;
    // Ordered settings sections. Admin-only sections are appended below.
    // [label, icon, route name, active-match pattern]. Rendered only when the
    // route exists (Route::has) so the partial stays safe if a section is absent.
    $tabs = [
        ['General', 'settings', 'settings.general.edit', 'settings.general.*'],
        ['Storage', 'cloud', 'settings.storage.index', 'settings.storage.*'],
        ['Notifications', 'bell', 'settings.notifications.edit', 'settings.notifications.*'],
        ['Branding', 'edit', 'settings.branding.edit', 'settings.branding.*'],
        ['API Tokens', 'key', 'settings.tokens.index', 'settings.tokens.*'],
        ['Password', 'lock', 'settings.password.edit', 'settings.password.*'],
        ['2FA', 'shield', 'settings.2fa.show', 'settings.2fa.*'],
        ['License', 'shield', 'settings.license.edit', 'settings.license.*'],
        ['Maintenance', 'refresh', 'settings.maintenance.edit', 'settings.maintenance.*'],
    ];
    if (auth()->check() && auth()->user()->isAdmin()) {
        $tabs[] = ['Host & SSL', 'globe', 'settings.host.edit', 'settings.host.*'];
        $tabs[] = ['Firewall', 'shield', 'settings.firewall.index', 'settings.firewall.*'];
        $tabs[] = ['Users', 'users', 'settings.users.index', 'settings.users.*'];
        $tabs[] = ['Audit', 'book', 'settings.audit.index', 'settings.audit.*'];
    }
    $tabs = array_values(array_filter($tabs, fn ($t) => RouteFacade::has($t[2])));
@endphp
@if (count($tabs))
    {{-- Plain CSS (not Tailwind) so the purged build can't strip it. Vertical
         stacked-pill menu; the layout places this in a sticky left column. --}}
    <style>
        .settings-shell{display:grid;grid-template-columns:230px minmax(0,1fr);gap:1.5rem;align-items:start;}
        .settings-aside{position:sticky;top:5rem;}
        @media (max-width:768px){.settings-shell{grid-template-columns:1fr;}.settings-aside{position:static;}}
        .st-menu{display:flex;flex-direction:column;gap:.2rem;background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:.4rem;box-shadow:0 1px 2px rgba(0,0,0,.05);}
        .st-item{display:flex;align-items:center;gap:.6rem;padding:.55rem .7rem;border-radius:.55rem;font-size:.875rem;font-weight:500;color:#475569;text-decoration:none;transition:background .15s,color .15s;}
        .st-item:hover{background:#f1f5f9;color:#0f172a;}
        .st-item.is-active{background:#1e293b;color:#fff;font-weight:600;}
        .st-item svg{width:1.05rem;height:1.05rem;flex:0 0 auto;}
    </style>
    <nav class="st-menu" aria-label="Settings sections">
        @foreach ($tabs as [$label, $icon, $routeName, $pattern])
            @php $active = request()->routeIs($pattern); @endphp
            <a href="{{ route($routeName) }}" class="st-item {{ $active ? 'is-active' : '' }}" @if ($active) aria-current="page" @endif>
                <x-icon :name="$icon" />
                <span>{{ $label }}</span>
            </a>
        @endforeach
    </nav>
@endif
