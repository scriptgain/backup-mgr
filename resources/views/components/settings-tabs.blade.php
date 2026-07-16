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
    <div {{ $attributes->merge(['class' => 'mb-6']) }}>
        <nav class="flex flex-wrap gap-1.5 rounded-xl border border-slate-200 bg-white p-1.5 shadow-sm" aria-label="Settings sections">
            @foreach ($tabs as [$label, $icon, $routeName, $pattern])
                @php $active = request()->routeIs($pattern); @endphp
                <a href="{{ route($routeName) }}"
                    @if ($active) aria-current="page" @endif
                    class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium whitespace-nowrap transition {{ $active ? 'bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                    <x-icon :name="$icon" class="w-4 h-4 shrink-0" />
                    {{ $label }}
                </a>
            @endforeach
        </nav>
    </div>
@endif
