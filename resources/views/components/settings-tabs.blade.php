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
    {{-- Plain CSS (not Tailwind) so the purged build can't strip it. --}}
    <style>
        .st-tabs{display:flex;flex-wrap:wrap;gap:.4rem;background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:.4rem;box-shadow:0 1px 2px rgba(0,0,0,.05);margin-bottom:1.5rem;}
        .st-tab{display:inline-flex;align-items:center;gap:.45rem;padding:.5rem .85rem;border-radius:.55rem;font-size:.8125rem;font-weight:500;color:#475569;white-space:nowrap;text-decoration:none;line-height:1.2;transition:background .15s,color .15s;}
        .st-tab:hover{background:#f1f5f9;color:#0f172a;}
        .st-tab.is-active{background:#1e293b;color:#fff;font-weight:600;}
        .st-tab svg{width:1rem;height:1rem;flex:0 0 auto;}
    </style>
    <nav class="st-tabs" aria-label="Settings sections">
        @foreach ($tabs as [$label, $icon, $routeName, $pattern])
            @php $active = request()->routeIs($pattern); @endphp
            <a href="{{ route($routeName) }}" class="st-tab {{ $active ? 'is-active' : '' }}" @if ($active) aria-current="page" @endif>
                <x-icon :name="$icon" />
                {{ $label }}
            </a>
        @endforeach
    </nav>
@endif
