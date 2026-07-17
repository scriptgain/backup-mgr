@props(['title' => null, 'maxWidth' => 'max-w-7xl'])
@php
    $u = auth()->user();
    $initials = \Illuminate\Support\Str::of($u?->name ?? 'Admin')
        ->explode(' ')->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
@endphp
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title . ' — ' . config('brand.name') : config('brand.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ route('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="64x64" href="{{ route('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ route('favicon.apple') }}">
    <x-tailwind-cdn />
    <x-accent-style />
</head>
<body class="h-full min-h-full bg-slate-50">
<div class="min-h-full flex flex-col">

    {{-- Brand accent hairline --}}
    <div class="h-0.5 bg-gradient-to-r from-brand-600 via-brand-400 to-brand-600"></div>

    {{-- Dark top utility bar (house style) --}}
    <div class="bg-chrome text-slate-300 text-sm ring-1 ring-inset ring-white/5">
        <div class="{{ $maxWidth }} mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex h-12 items-center justify-between gap-4">
                <x-brand class="text-white" />
                <div class="flex items-center gap-2 sm:gap-3">
                    <span class="hidden sm:inline-flex items-center gap-1.5 rounded-full bg-emerald-400/10 px-2.5 py-1 text-xs font-medium text-emerald-300 ring-1 ring-inset ring-emerald-400/20">
                        <span class="relative flex h-1.5 w-1.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                        </span>
                        Operational
                    </span>
                    <span class="hidden sm:inline-flex items-center gap-1 rounded-full bg-white/10 px-2.5 py-1 text-xs font-medium text-white ring-1 ring-inset ring-white/10">
                        <x-icon name="cloud" class="w-3.5 h-3.5" /> Production
                    </span>
                    <a href="{{ \Illuminate\Support\Facades\Route::has('docs') ? route('docs') : '#' }}" target="_blank" rel="noopener" title="Documentation" class="hidden md:inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition">
                        <x-icon name="book" class="w-4 h-4" />
                    </a>
                    <span class="hidden sm:inline-block h-5 w-px bg-white/10"></span>
                    <x-dropdown align="right">
                        <x-slot:trigger>
                            <button class="inline-flex items-center gap-2 rounded-full py-1 pl-1 pr-2 hover:bg-white/10 transition">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-brand-500/20 text-brand-200 text-xs font-semibold ring-1 ring-brand-400/40">{{ $initials }}</span>
                                <span class="hidden sm:block text-xs font-medium text-slate-200 max-w-[8rem] truncate">{{ \Illuminate\Support\Str::of($u?->name ?? 'Admin')->explode(' ')->first() }}</span>
                                <x-icon name="chevron-down" class="w-4 h-4 text-slate-400" />
                            </button>
                        </x-slot:trigger>
                        @if ($u)
                            <div class="px-3 py-2.5 border-b border-slate-100">
                                <p class="text-sm font-medium text-slate-900 truncate">{{ $u->name }}</p>
                                <p class="text-xs text-slate-500 truncate">{{ $u->email }}</p>
                                @if ($u->isAdmin())<span class="mt-1.5 inline-flex items-center rounded-full bg-brand-50 px-2 py-0.5 text-[11px] font-medium text-brand-700 ring-1 ring-inset ring-brand-200">Admin</span>@endif
                            </div>
                        @endif
                        <x-dropdown-item icon="settings" href="{{ route('settings.index') }}">Settings</x-dropdown-item>
                        @if ($u && $u->isAdmin())
                            <x-dropdown-item icon="users" href="{{ route('settings.users.index') }}">Users & Admins</x-dropdown-item>
                            <x-dropdown-item icon="book" href="{{ route('settings.audit.index') }}">Audit Log</x-dropdown-item>
                        @endif
                        <div class="my-1 border-t border-slate-100"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-sm text-left text-rose-600 hover:bg-rose-50">
                                <x-icon name="x-circle" class="w-4 h-4 shrink-0" /> Sign Out
                            </button>
                        </form>
                    </x-dropdown>
                </div>
            </div>
        </div>
    </div>

    {{-- Main navbar (light, sticky, mobile-friendly). Grouped into a few
         top-level items; related sections collapse under dropdowns. --}}
    @php
        $nav = [
            ['type' => 'link', 'label' => 'Dashboard', 'href' => route('dashboard'), 'icon' => 'dashboard',
                'active' => request()->routeIs('dashboard')],
            ['type' => 'group', 'label' => 'Infrastructure', 'icon' => 'server',
                'active' => request()->routeIs('locations.*', 'directors.*', 'hosts.*', 'repositories.*'),
                'items' => [
                    ['Locations', route('locations.index'), 'home', request()->routeIs('locations.*')],
                    ['Directors', route('directors.index'), 'cloud', request()->routeIs('directors.*')],
                    ['Hosts', route('hosts.index'), 'server', request()->routeIs('hosts.*')],
                    ['Repositories', route('repositories.index'), 'archive', request()->routeIs('repositories.*')],
                ]],
            ['type' => 'group', 'label' => 'Backups', 'icon' => 'clock',
                'active' => request()->routeIs('jobs.*', 'schedule-templates.*', 'snapshots.*', 'restores.*'),
                'items' => [
                    ['Backup Jobs', route('jobs.index'), 'clock', request()->routeIs('jobs.*')],
                    ['Schedule Templates', route('schedule-templates.index'), 'clock', request()->routeIs('schedule-templates.*')],
                    ['Snapshots', route('snapshots.index'), 'archive', request()->routeIs('snapshots.*')],
                    ['Restores', route('restores.index'), 'restore', request()->routeIs('restores.*')],
                ]],
        ];
        // If the current route is inside a top-nav group, expose that group's items
        // so the layout can render a left menu for it (same pattern as settings).
        $activeGroupItems = null;
        foreach ($nav as $navItem) {
            if (($navItem['type'] ?? '') === 'group' && ($navItem['active'] ?? false)) {
                $activeGroupItems = $navItem['items'];
                break;
            }
        }
    @endphp
    <header x-data="{ mobileOpen: false }" class="bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/80 border-b border-slate-200 sticky top-0 z-30">
        <div class="{{ $maxWidth }} mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex h-14 items-center justify-between gap-3">
                <div class="flex items-center gap-1 min-w-0">
                    <button type="button" @click="mobileOpen = !mobileOpen" :aria-expanded="mobileOpen.toString()" aria-label="Toggle menu"
                        class="lg:hidden inline-flex items-center justify-center w-9 h-9 rounded-lg text-slate-600 hover:bg-slate-100 transition shrink-0">
                        <svg x-show="!mobileOpen" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" /></svg>
                        <svg x-show="mobileOpen" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                    <nav class="hidden lg:flex items-center gap-1">
                        @foreach ($nav as $item)
                            @if ($item['type'] === 'link')
                                <x-nav-link :href="$item['href']" :icon="$item['icon']" :active="$item['active']">{{ $item['label'] }}</x-nav-link>
                            @else
                                @php $gActive = $item['active']; @endphp
                                <div x-data="{ open: false }" class="relative" @click.outside="open = false" @keydown.escape="open = false">
                                    <button type="button" @click="open = !open" :aria-expanded="open.toString()"
                                        @class([
                                            'inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition ring-1 ring-inset',
                                            'text-brand-700 bg-brand-50 ring-brand-200' => $gActive,
                                            'text-slate-600 ring-transparent hover:text-slate-900 hover:bg-slate-100 hover:ring-slate-200' => ! $gActive,
                                        ])>
                                        <x-icon :name="$item['icon']" class="w-4 h-4 shrink-0" />
                                        {{ $item['label'] }}
                                        <x-icon name="chevron-down" class="w-4 h-4 -mr-0.5 text-slate-400 transition-transform" ::class="open && 'rotate-180'" />
                                    </button>
                                    <div x-show="open" x-cloak x-transition
                                         class="absolute left-0 z-40 mt-2 w-56 origin-top-left rounded-lg bg-white shadow-lg ring-1 ring-slate-200 py-1"
                                         @click="open = false">
                                        @foreach ($item['items'] as [$label, $href, $icon, $active])
                                            <a href="{{ $href }}" @class([
                                                'flex items-center gap-2.5 px-3 py-2 text-sm transition',
                                                'text-brand-700 bg-brand-50 font-medium' => $active,
                                                'text-slate-700 hover:bg-slate-100' => ! $active,
                                            ])>
                                                <x-icon :name="$icon" class="w-4 h-4 shrink-0 {{ $active ? 'text-brand-600' : 'text-slate-400' }}" /> {{ $label }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </nav>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <x-button href="{{ route('jobs.create') }}" icon="plus" size="sm"><span class="hidden sm:inline">New Backup Job</span><span class="sm:hidden">New Job</span></x-button>
                </div>
            </div>
        </div>
        {{-- Mobile slide-down menu --}}
        <div x-show="mobileOpen" x-cloak
             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
             class="lg:hidden border-t border-slate-100 bg-white shadow-sm">
            <nav class="{{ $maxWidth }} mx-auto px-4 sm:px-6 py-3 space-y-3">
                @foreach ($nav as $item)
                    @if ($item['type'] === 'link')
                        <a href="{{ $item['href'] }}" @class([
                            'flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                            'bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200' => $item['active'],
                            'text-slate-600 hover:bg-slate-100' => ! $item['active'],
                        ])>
                            <x-icon :name="$item['icon']" class="w-4 h-4 shrink-0" /> {{ $item['label'] }}
                        </a>
                    @else
                        <div>
                            <p class="px-3 pb-1.5 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $item['label'] }}</p>
                            <div class="grid grid-cols-2 gap-1.5">
                                @foreach ($item['items'] as [$label, $href, $icon, $active])
                                    <a href="{{ $href }}" @class([
                                        'flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                                        'bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200' => $active,
                                        'text-slate-600 hover:bg-slate-100' => ! $active,
                                    ])>
                                        <x-icon :name="$icon" class="w-4 h-4 shrink-0" /> {{ $label }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </nav>
        </div>
    </header>

    {{-- Breadcrumbs (auto-derived from the current route + page title) --}}
    @php
        $rn = request()->route()?->getName() ?? '';
        $section = strtok($rn, '.');
        $sectionMap = [
            'locations' => ['Locations', 'locations.index'],
            'directors' => ['Directors', 'directors.index'],
            'hosts' => ['Hosts', 'hosts.index'],
            'repositories' => ['Repositories', 'repositories.index'],
            'jobs' => ['Backup Jobs', 'jobs.index'],
            'snapshots' => ['Snapshots', 'snapshots.index'],
            'restores' => ['Restores', 'restores.index'],
            'schedule-templates' => ['Schedule Templates', 'schedule-templates.index'],
            'settings' => ['Settings', 'settings.index'],
        ];
        $crumbs = [];
        if (isset($sectionMap[$section])) {
            [$secLabel, $secIndex] = $sectionMap[$section];
            $isIndex = $rn === $secIndex;
            $crumbs[] = ['label' => $secLabel, 'href' => $isIndex ? null : route($secIndex)];
            if (! $isIndex && $title && $title !== $secLabel) {
                $crumbs[] = ['label' => $title, 'href' => null];
            }
        }
    @endphp
    @if ($rn !== 'dashboard' && count($crumbs))
        <div class="bg-white border-b border-slate-200">
            <div class="{{ $maxWidth }} mx-auto px-4 sm:px-6 lg:px-8">
                <nav class="flex items-center gap-2 h-10 text-sm" aria-label="Breadcrumb">
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-slate-500 hover:text-brand-700 transition">
                        <x-icon name="home" class="w-4 h-4" /> Dashboard
                    </a>
                    @foreach ($crumbs as $c)
                        <x-icon name="chevron-right" class="w-4 h-4 text-slate-300 shrink-0" />
                        @if ($c['href'])
                            <a href="{{ $c['href'] }}" class="text-slate-500 hover:text-brand-700 transition">{{ $c['label'] }}</a>
                        @else
                            <span class="font-medium text-slate-900 truncate max-w-[18rem]">{{ $c['label'] }}</span>
                        @endif
                    @endforeach
                </nav>
            </div>
        </div>
    @endif

    {{-- Page content --}}
    <main class="flex-1 py-8">
        <div class="{{ $maxWidth }} mx-auto px-4 sm:px-6 lg:px-8">
            <x-license-banner class="mb-6" />
            <x-update-banner />
            @if (session('status'))
                <div class="mb-6"><x-alert type="success">{{ session('status') }}</x-alert></div>
            @endif
            @if (session('warning'))
                <div class="mb-6"><x-alert type="warn">{{ session('warning') }}</x-alert></div>
            @endif
            @if (request()->routeIs('settings.*'))
                <div class="settings-shell">
                    <aside class="settings-aside"><x-settings-tabs /></aside>
                    <div>{{ $slot }}</div>
                </div>
            @elseif ($activeGroupItems)
                <div class="settings-shell">
                    <aside class="settings-aside"><x-side-menu :items="$activeGroupItems" /></aside>
                    <div>{{ $slot }}</div>
                </div>
            @else
                {{ $slot }}
            @endif
        </div>
    </main>

    {{-- Footer --}}
    <footer class="border-t border-slate-200 bg-white">
        <div class="{{ $maxWidth }} mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
            <span>{{ config('brand.name') }} &middot; {{ config('brand.tagline') }}</span>
            <span class="tabular">v{{ \App\Services\UpdateService::currentVersion() }} &middot; kopia 0.23.1</span>
        </div>
    </footer>

</div>

{{-- Global tooltip: a single fixed-position element on <body> that reads
     [data-tip]. Fixed positioning means no ancestor's overflow can ever clip
     it (unlike a CSS ::after tip). Supports multi-line tips (newlines in the
     attribute render as line breaks). --}}
<style>
    .vx-tip{position:fixed;z-index:9999;max-width:22rem;padding:.5rem .625rem;border-radius:.5rem;background:#0f172a;color:#f8fafc;font-size:.75rem;line-height:1.2rem;white-space:pre-line;box-shadow:0 8px 24px rgba(2,6,23,.22);pointer-events:none;opacity:0;transition:opacity .12s ease;display:none}
    .vx-tip strong{color:#fff}
</style>
<script>
    (function () {
        var tip;
        function ensure() {
            if (!tip) { tip = document.createElement('div'); tip.className = 'vx-tip'; document.body.appendChild(tip); }
            return tip;
        }
        function show(el) {
            var t = el.getAttribute('data-tip');
            if (!t) return;
            var n = ensure();
            n.textContent = t;
            n.style.display = 'block';
            n.style.opacity = '0';
            var r = el.getBoundingClientRect(), tr = n.getBoundingClientRect();
            var left = Math.max(8, Math.min(r.left + r.width / 2 - tr.width / 2, window.innerWidth - tr.width - 8));
            var top = r.top - tr.height - 8;
            if (top < 8) top = r.bottom + 8; // flip below when there's no room above
            n.style.left = left + 'px';
            n.style.top = top + 'px';
            n.style.opacity = '1';
        }
        function hide() { if (tip) { tip.style.opacity = '0'; tip.style.display = 'none'; } }
        document.addEventListener('mouseover', function (e) { var el = e.target.closest('[data-tip]'); if (el) show(el); });
        document.addEventListener('mouseout', function (e) { var el = e.target.closest('[data-tip]'); if (el) hide(); });
        document.addEventListener('scroll', hide, true);
        window.addEventListener('resize', hide);
    })();
</script>
</body>
</html>
