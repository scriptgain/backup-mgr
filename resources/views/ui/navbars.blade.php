<!doctype html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Navbar Gallery</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            DEFAULT: '#0f62fe',
                            50: '#edf5ff', 100: '#d0e2ff', 200: '#a6c8ff', 300: '#78a9ff',
                            400: '#4589ff', 500: '#0f62fe', 600: '#0353e9', 700: '#0043ce',
                            800: '#002d9c', 900: '#001d6c', 950: '#001141',
                        },
                        navy: '#0b2545',
                        ink: '#0b2545',
                    },
                    fontFamily: {
                        sans: ['IBM Plex Sans', 'system-ui', 'sans-serif'],
                        mono: ['IBM Plex Mono', 'monospace'],
                    },
                },
            },
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none !important}</style>
</head>
@php
    /*
     | Inline heroicons (outline, 24x24). Rendered through a tiny closure so
     | every nav link can carry a left-aligned icon without pulling a fixed
     | icon component. Add a key here to expose a new glyph.
     */
    $icons = [
        'cube'      => 'm21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9',
        'squares'   => 'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25A2.25 2.25 0 0 1 13.5 8.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z',
        'server'    => 'M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6m-16.5-3a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3m-19.5 0a4.5 4.5 0 0 1 .9-2.7L5.737 5.1a3.375 3.375 0 0 1 2.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 0 1 .9 2.7m0 0a3 3 0 0 1-3 3m0 3h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Zm-3 6h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Z',
        'currency'  => 'M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'book'      => 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25',
        'document'  => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z',
        'building'  => 'M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z',
        'lifebuoy'  => 'M16.712 4.33a9.027 9.027 0 0 1 1.652 1.306c.51.51.944 1.064 1.306 1.652M16.712 4.33l-3.448 4.138m3.448-4.138a9.014 9.014 0 0 0-9.424 0M19.67 7.288l-4.138 3.448m4.138-3.448a9.014 9.014 0 0 1 0 9.424m-4.138-5.976a3.736 3.736 0 0 0-.88-1.388 3.737 3.737 0 0 0-1.388-.88m2.268 2.268a3.765 3.765 0 0 1 0 2.528m-2.268-4.796a3.765 3.765 0 0 0-2.528 0m4.796 4.796c-.181.506-.475.982-.88 1.388a3.736 3.736 0 0 1-1.388.88m2.268-2.268 4.138 3.448m0 0a9.027 9.027 0 0 1-1.306 1.652c-.51.51-1.064.944-1.652 1.306m0 0-3.448-4.138m3.448 4.138a9.014 9.014 0 0 1-9.424 0m5.976-4.138a3.765 3.765 0 0 1-2.528 0m0 0a3.736 3.736 0 0 1-1.388-.88 3.737 3.737 0 0 1-.88-1.388m2.268 2.268L7.288 19.67m0 0a9.024 9.024 0 0 1-1.652-1.306 9.027 9.027 0 0 1-1.306-1.652m0 0 4.138-3.448M4.33 16.712a9.014 9.014 0 0 1 0-9.424m4.138 5.976a3.765 3.765 0 0 1 0-2.528m0 0c.181-.506.475-.982.88-1.388a3.736 3.736 0 0 1 1.388-.88m-2.268 2.268L4.33 7.288m6.406 1.18L7.288 4.33m0 0a9.024 9.024 0 0 0-1.652 1.306A9.025 9.025 0 0 0 4.33 7.288',
        'phone'     => 'M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z',
        'envelope'  => 'M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75',
        'globe'     => 'M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418',
        'chevron'   => 'm19.5 8.25-7.5 7.5-7.5-7.5',
        'user'      => 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z',
        'search'    => 'm21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z',
        'bars'      => 'M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5',
        'shield'    => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z',
        'chart'     => 'M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25Z',
        'users'     => 'M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z',
        'bolt'      => 'm3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z',
        'academic'  => 'M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5',
        'newspaper' => 'M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z',
        'briefcase' => 'M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z',
        'sparkles'  => 'M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z',
        'pin'       => 'M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z',
        'clock'     => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'cog'       => 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.241.437-.613.43-.992a6.932 6.932 0 0 1 0-.255c.007-.378-.138-.75-.43-.991l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
        'rocket'    => 'M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.448 14.9 14.9 0 0 1 .06-.312m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306 4.493 4.493 0 0 0 4.306-1.758M16.5 9a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z',
        'phone-arrow' => 'M20.25 3.75v4.5m0-4.5h-4.5m4.5 0-6 6m3 12c-8.284 0-15-6.716-15-15V4.5A2.25 2.25 0 0 1 4.5 2.25h1.372c.516 0 .966.351 1.091.852l1.106 4.423c.11.44-.054.902-.417 1.173l-1.293.97a1.062 1.062 0 0 0-.38 1.21 12.035 12.035 0 0 0 7.143 7.143c.441.162.928-.004 1.21-.38l.97-1.293a1.125 1.125 0 0 1 1.173-.417l4.423 1.106c.5.125.852.575.852 1.091V19.5a2.25 2.25 0 0 1-2.25 2.25h-.75Z',
    ];
    $icon = function ($name, $class = 'w-4 h-4') use ($icons) {
        $d = $icons[$name] ?? $icons['cube'];
        // Some paths carry two subpaths separated by a space+M; split so both draw.
        $paths = preg_split('/(?<=Z|z) (?=M)/', $d);
        $inner = '';
        foreach ($paths as $p) { $inner .= '<path stroke-linecap="round" stroke-linejoin="round" d="'.$p.'"/>'; }
        return '<svg class="'.$class.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">'.$inner.'</svg>';
    };

    $brand = 'ScriptGain';

    // Logo lockup: inline mark + wordmark, never a chip/box behind it.
    $logo = function ($textClass = 'text-slate-900', $markClass = 'text-brand-600', $markIcon = 'cube') use ($brand, $icon) {
        return '<a href="#" class="inline-flex items-center gap-2 shrink-0">'
            .'<span class="'.$markClass.'">'.$icon($markIcon, 'w-7 h-7').'</span>'
            .'<span class="text-lg font-bold tracking-tight '.$textClass.'">'.$brand.'</span></a>';
    };

    // A styled desktop link with a left-aligned icon.
    $deskLink = function ($name, $label, $extra = 'text-slate-700 hover:text-brand-700', $iconClass = 'w-4 h-4') use ($icon) {
        return '<a href="#" class="inline-flex items-center gap-2 text-sm font-medium transition-colors '.$extra.'">'
            .$icon($name, $iconClass).'<span>'.$label.'</span></a>';
    };

    // Standard mobile drop panel built from a link set [[icon,label], ...].
    $mobilePanel = function (array $links) use ($icon) {
        $out = '<div x-show="open" x-cloak class="md:hidden border-t border-slate-200 bg-white px-4 py-3 space-y-1">';
        foreach ($links as $l) {
            $out .= '<a href="#" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">'
                .$icon($l[0], 'w-5 h-5 text-slate-500').'<span>'.$l[1].'</span></a>';
        }
        $out .= '<a href="#" class="mt-2 flex items-center justify-center rounded-md bg-brand-600 px-3 py-2.5 text-sm font-semibold text-white">Request Demo</a></div>';
        return $out;
    };

    // Light dummy page body so the dark bars read against real page context.
    $body = function ($kicker, $title, $desc = '') {
        $desc = $desc ?: 'A unified platform for backup, recovery, and data protection across every workload in your enterprise, from bare metal to cloud native.';
        $cards = [
            ['Immutable Snapshots', 'Ransomware resilient copies that cannot be altered or deleted.'],
            ['Global Restore', 'Recover any workload to any region in minutes, not hours.'],
            ['Policy Automation', 'Set retention once and let the scheduler enforce it fleet wide.'],
        ];
        $cardHtml = '';
        foreach ($cards as $c) {
            $cardHtml .= '<div class="rounded-xl border border-slate-200 bg-white p-6">'
                .'<h3 class="text-base font-semibold text-slate-900">'.$c[0].'</h3>'
                .'<p class="mt-2 text-sm text-slate-600">'.$c[1].'</p></div>';
        }
        return '<main class="bg-slate-50">'
            .'<section class="mx-auto max-w-7xl px-4 py-16 sm:py-20">'
            .'<p class="text-xs font-semibold uppercase tracking-widest text-brand-600">'.$kicker.'</p>'
            .'<h1 class="mt-3 max-w-2xl text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">'.$title.'</h1>'
            .'<p class="mt-4 max-w-xl text-slate-600">'.$desc.'</p>'
            .'<div class="mt-8 flex flex-wrap gap-3">'
            .'<a href="#" class="inline-flex items-center rounded-md bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Request Demo</a>'
            .'<a href="#" class="inline-flex items-center rounded-md bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-100">Contact Sales</a>'
            .'</div></section>'
            .'<section class="mx-auto max-w-7xl px-4 pb-20"><div class="grid gap-6 sm:grid-cols-3">'.$cardHtml.'</div></section>'
            .'</main>';
    };

    // Small corner badge naming the current design.
    $badge = function ($n) {
        return '<div class="pointer-events-none fixed bottom-4 right-4 z-40 rounded-full bg-navy px-3 py-1 text-xs font-semibold text-white shadow-lg ring-1 ring-white/10">Design '.sprintf('%02d', $n).'</div>';
    };
@endphp
<body class="bg-slate-100 font-sans text-slate-900 antialiased" x-data="{ current: 0, total: 20 }">

    {{-- Cycler control bar --}}
    <div class="sticky top-0 z-50 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-3 px-4 py-3">
            <div class="flex items-center gap-3">
                <span class="text-sm font-semibold text-slate-900">Navbar <span x-text="current + 1"></span> / 20</span>
                <div class="flex items-center gap-1.5">
                    <button type="button" @click="current = (current + total - 1) % total"
                        class="inline-flex items-center gap-1 rounded-md bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">
                        {!! $icon('chevron', 'w-4 h-4 rotate-90') !!} Previous
                    </button>
                    <button type="button" @click="current = (current + 1) % total"
                        class="inline-flex items-center gap-1 rounded-md bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">
                        Next {!! $icon('chevron', 'w-4 h-4 -rotate-90') !!}
                    </button>
                </div>
            </div>
            <div class="flex flex-1 flex-wrap items-center gap-1 sm:justify-end">
                @for ($i = 0; $i < 20; $i++)
                    <button type="button" @click="current = {{ $i }}"
                        :class="current === {{ $i }} ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="h-7 w-7 rounded-md text-xs font-semibold transition-colors">{{ $i + 1 }}</button>
                @endfor
            </div>
        </div>
    </div>

    {{-- ============================================================= --}}
    {{-- Design 01: Classic left logo, underline-active nav, dual CTA  --}}
    {{-- ============================================================= --}}
    <div x-show="current === 0" x-cloak>
        <header x-data="{ open: false }">
            <div class="bg-navy text-slate-300">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-2 text-xs">
                    <div class="flex items-center gap-4">
                        <a href="#" class="inline-flex items-center gap-1.5 hover:text-white">{!! $icon('phone', 'w-3.5 h-3.5') !!} (555) 010 2200</a>
                        <a href="#" class="hidden items-center gap-1.5 hover:text-white sm:inline-flex">{!! $icon('envelope', 'w-3.5 h-3.5') !!} sales@scriptgain.com</a>
                    </div>
                    <div class="flex items-center gap-4">
                        <a href="#" class="hidden hover:text-white sm:inline">Support</a>
                        <a href="#" class="hidden hover:text-white sm:inline">Partner Portal</a>
                        <a href="#" class="inline-flex items-center gap-1.5 font-semibold text-white">{!! $icon('user', 'w-3.5 h-3.5') !!} Sign In</a>
                    </div>
                </div>
            </div>
            <div class="border-b border-slate-200 bg-white">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4">
                    {!! $logo() !!}
                    <nav class="hidden items-center gap-7 md:flex">
                        <a href="#" class="inline-flex items-center gap-2 border-b-2 border-brand-600 pb-0.5 text-sm font-semibold text-slate-900">{!! $icon('cube', 'w-4 h-4') !!} Products</a>
                        {!! $deskLink('squares', 'Solutions', 'text-slate-700 hover:text-brand-700 border-b-2 border-transparent hover:border-brand-300 pb-0.5') !!}
                        {!! $deskLink('currency', 'Pricing', 'text-slate-700 hover:text-brand-700 border-b-2 border-transparent hover:border-brand-300 pb-0.5') !!}
                        {!! $deskLink('book', 'Resources', 'text-slate-700 hover:text-brand-700 border-b-2 border-transparent hover:border-brand-300 pb-0.5') !!}
                        {!! $deskLink('building', 'Company', 'text-slate-700 hover:text-brand-700 border-b-2 border-transparent hover:border-brand-300 pb-0.5') !!}
                    </nav>
                    <div class="flex items-center gap-3">
                        <a href="#" class="hidden text-sm font-semibold text-slate-700 hover:text-brand-700 md:inline">Sign In</a>
                        <a href="#" class="hidden rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 md:inline-flex">Request Demo</a>
                        <button type="button" class="inline-flex rounded-md p-2 text-slate-600 hover:bg-slate-100 md:hidden" @click="open = !open" aria-label="Toggle Menu">{!! $icon('bars', 'w-6 h-6') !!}</button>
                    </div>
                </div>
                {!! $mobilePanel([['cube','Products'],['squares','Solutions'],['currency','Pricing'],['book','Resources'],['building','Company']]) !!}
            </div>
        </header>
        {!! $body('Enterprise Data Protection', 'Backup and Recovery, Built for Scale') !!}
        {!! $badge(1) !!}
    </div>

    {{-- ============================================================= --}}
    {{-- Design 02: Centered logo, split nav, status pill utility bar  --}}
    {{-- ============================================================= --}}
    <div x-show="current === 1" x-cloak>
        <header x-data="{ open: false }">
            <div class="bg-slate-900 text-slate-300">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-2 text-xs">
                    <span class="inline-flex items-center gap-2 rounded-full bg-emerald-500/15 px-2.5 py-1 font-medium text-emerald-300 ring-1 ring-emerald-400/30">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> All Systems Operational
                    </span>
                    <div class="flex items-center gap-4">
                        <a href="#" class="hover:text-white">Docs</a>
                        <a href="#" class="inline-flex items-center gap-1.5 hover:text-white">{!! $icon('globe', 'w-3.5 h-3.5') !!} EN</a>
                        <a href="#" class="inline-flex items-center gap-1.5 font-semibold text-white">{!! $icon('user', 'w-3.5 h-3.5') !!} Sign In</a>
                    </div>
                </div>
            </div>
            <div class="border-b border-slate-200 bg-white">
                <div class="mx-auto grid max-w-7xl grid-cols-2 items-center px-4 py-4 md:grid-cols-3">
                    <nav class="hidden items-center gap-6 md:flex">
                        {!! $deskLink('cube', 'Platform') !!}
                        {!! $deskLink('squares', 'Solutions') !!}
                        {!! $deskLink('chart', 'Insights') !!}
                    </nav>
                    <div class="flex justify-start md:justify-center">{!! $logo() !!}</div>
                    <div class="flex items-center justify-end gap-6">
                        <nav class="hidden items-center gap-6 md:flex">
                            {!! $deskLink('currency', 'Pricing') !!}
                            {!! $deskLink('building', 'Company') !!}
                        </nav>
                        <a href="#" class="hidden rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 md:inline-flex">Get Started</a>
                        <button type="button" class="inline-flex rounded-md p-2 text-slate-600 hover:bg-slate-100 md:hidden" @click="open = !open" aria-label="Toggle Menu">{!! $icon('bars', 'w-6 h-6') !!}</button>
                    </div>
                </div>
                {!! $mobilePanel([['cube','Platform'],['squares','Solutions'],['chart','Insights'],['currency','Pricing'],['building','Company']]) !!}
            </div>
        </header>
        {!! $body('Continuous Protection', 'One Console for Every Workload You Run') !!}
        {!! $badge(2) !!}
    </div>

    {{-- ============================================================= --}}
    {{-- Design 03: Search field inside the main navbar, outline CTA   --}}
    {{-- ============================================================= --}}
    <div x-show="current === 2" x-cloak>
        <header x-data="{ open: false }">
            <div class="bg-navy text-slate-300">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-2 text-xs">
                    <a href="#" class="inline-flex items-center gap-1.5 hover:text-white">{!! $icon('phone', 'w-3.5 h-3.5') !!} Talk to Sales</a>
                    <div class="flex items-center gap-4">
                        <a href="#" class="hover:text-white">Status</a>
                        <a href="#" class="hover:text-white">Support</a>
                        <a href="#" class="inline-flex items-center gap-1.5 font-semibold text-white">{!! $icon('user', 'w-3.5 h-3.5') !!} Sign In</a>
                    </div>
                </div>
            </div>
            <div class="border-b border-slate-200 bg-white">
                <div class="mx-auto flex max-w-7xl items-center gap-6 px-4 py-4">
                    {!! $logo() !!}
                    <nav class="hidden items-center gap-6 lg:flex">
                        {!! $deskLink('cube', 'Products') !!}
                        {!! $deskLink('squares', 'Solutions') !!}
                        {!! $deskLink('book', 'Resources') !!}
                    </nav>
                    <div class="ml-auto hidden items-center rounded-md bg-slate-100 px-3 py-2 md:flex">
                        <span class="text-slate-400">{!! $icon('search', 'w-4 h-4') !!}</span>
                        <input type="text" placeholder="Search Docs and Products" class="w-40 border-0 bg-transparent px-2 text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none lg:w-56">
                    </div>
                    <a href="#" class="hidden rounded-md px-4 py-2 text-sm font-semibold text-brand-700 ring-1 ring-brand-600 hover:bg-brand-50 md:inline-flex">Contact Sales</a>
                    <button type="button" class="ml-auto inline-flex rounded-md p-2 text-slate-600 hover:bg-slate-100 md:hidden" @click="open = !open" aria-label="Toggle Menu">{!! $icon('bars', 'w-6 h-6') !!}</button>
                </div>
                {!! $mobilePanel([['cube','Products'],['squares','Solutions'],['book','Resources']]) !!}
            </div>
        </header>
        {!! $body('Search Everything', 'Find Any Backup, Policy, or Endpoint Fast') !!}
        {!! $badge(3) !!}
    </div>

    {{-- ============================================================= --}}
    {{-- Design 04: Mega-menu trigger with dropdown panel, social bar  --}}
    {{-- ============================================================= --}}
    <div x-show="current === 3" x-cloak>
        <header x-data="{ open: false, mega: false }">
            <div class="bg-navy text-slate-300">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-2 text-xs">
                    <a href="#" class="inline-flex items-center gap-1.5 hover:text-white">{!! $icon('envelope', 'w-3.5 h-3.5') !!} hello@scriptgain.com</a>
                    <div class="flex items-center gap-3">
                        <a href="#" class="hover:text-white" aria-label="LinkedIn">{!! $icon('briefcase', 'w-3.5 h-3.5') !!}</a>
                        <a href="#" class="hover:text-white" aria-label="Community">{!! $icon('users', 'w-3.5 h-3.5') !!}</a>
                        <a href="#" class="hover:text-white" aria-label="Blog">{!! $icon('newspaper', 'w-3.5 h-3.5') !!}</a>
                        <span class="h-3 w-px bg-slate-600"></span>
                        <a href="#" class="inline-flex items-center gap-1.5 font-semibold text-white">{!! $icon('user', 'w-3.5 h-3.5') !!} Sign In</a>
                    </div>
                </div>
            </div>
            <div class="relative border-b border-slate-200 bg-white">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4">
                    {!! $logo() !!}
                    <nav class="hidden items-center gap-7 md:flex">
                        <button type="button" @click="mega = !mega" class="inline-flex items-center gap-2 text-sm font-medium text-slate-700 hover:text-brand-700">
                            {!! $icon('cube', 'w-4 h-4') !!} Products {!! $icon('chevron', 'w-4 h-4 transition-transform') !!}
                        </button>
                        {!! $deskLink('squares', 'Solutions') !!}
                        {!! $deskLink('currency', 'Pricing') !!}
                        {!! $deskLink('building', 'Company') !!}
                    </nav>
                    <div class="flex items-center gap-3">
                        <a href="#" class="hidden rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 md:inline-flex">Request Demo</a>
                        <button type="button" class="inline-flex rounded-md p-2 text-slate-600 hover:bg-slate-100 md:hidden" @click="open = !open" aria-label="Toggle Menu">{!! $icon('bars', 'w-6 h-6') !!}</button>
                    </div>
                </div>
                <div x-show="mega" x-cloak @click.outside="mega = false" class="absolute inset-x-0 top-full z-30 hidden border-b border-slate-200 bg-white shadow-lg md:block">
                    <div class="mx-auto grid max-w-7xl grid-cols-3 gap-6 px-4 py-8">
                        @foreach ([['server','Backup Engine','Agentless and agent based capture'],['shield','Ransomware Shield','Immutable, air gapped copies'],['chart','Analytics','Capacity and cost forecasting'],['cog','Automation','Policy driven scheduling'],['globe','Cloud Connect','S3, Azure, and GCP targets'],['lifebuoy','Managed Service','24/7 white glove operations']] as $m)
                            <a href="#" class="flex items-start gap-3 rounded-lg p-3 hover:bg-slate-50">
                                <span class="text-brand-600">{!! $icon($m[0], 'w-6 h-6') !!}</span>
                                <span><span class="block text-sm font-semibold text-slate-900">{{ $m[1] }}</span><span class="block text-xs text-slate-500">{{ $m[2] }}</span></span>
                            </a>
                        @endforeach
                    </div>
                </div>
                {!! $mobilePanel([['cube','Products'],['squares','Solutions'],['currency','Pricing'],['building','Company']]) !!}
            </div>
        </header>
        {!! $body('Full Platform', 'Explore the Modules That Power Your Backups') !!}
        {!! $badge(4) !!}
    </div>

    {{-- ============================================================= --}}
    {{-- Design 05: Pill nav group with filled active pill             --}}
    {{-- ============================================================= --}}
    <div x-show="current === 4" x-cloak>
        <header x-data="{ open: false }">
            <div class="bg-brand-900 text-brand-100">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-2 text-xs">
                    <div class="flex items-center gap-4">
                        <a href="#" class="inline-flex items-center gap-1.5 hover:text-white">{!! $icon('phone', 'w-3.5 h-3.5') !!} (555) 010 2200</a>
                        <a href="#" class="hidden items-center gap-1.5 hover:text-white sm:inline-flex">{!! $icon('pin', 'w-3.5 h-3.5') !!} Global HQ, Austin</a>
                    </div>
                    <a href="#" class="inline-flex items-center gap-1.5 font-semibold text-white">{!! $icon('user', 'w-3.5 h-3.5') !!} Customer Login</a>
                </div>
            </div>
            <div class="border-b border-slate-200 bg-white">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4">
                    {!! $logo() !!}
                    <nav class="hidden items-center gap-1 rounded-full bg-slate-100 p-1 md:flex">
                        <a href="#" class="inline-flex items-center gap-2 rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white">{!! $icon('cube', 'w-4 h-4') !!} Products</a>
                        {!! $deskLink('squares', 'Solutions', 'text-slate-600 hover:text-slate-900 rounded-full px-4 py-2 hover:bg-white') !!}
                        {!! $deskLink('currency', 'Pricing', 'text-slate-600 hover:text-slate-900 rounded-full px-4 py-2 hover:bg-white') !!}
                        {!! $deskLink('book', 'Resources', 'text-slate-600 hover:text-slate-900 rounded-full px-4 py-2 hover:bg-white') !!}
                    </nav>
                    <div class="flex items-center gap-3">
                        <a href="#" class="hidden rounded-full bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-700 md:inline-flex">Get Started</a>
                        <button type="button" class="inline-flex rounded-md p-2 text-slate-600 hover:bg-slate-100 md:hidden" @click="open = !open" aria-label="Toggle Menu">{!! $icon('bars', 'w-6 h-6') !!}</button>
                    </div>
                </div>
                {!! $mobilePanel([['cube','Products'],['squares','Solutions'],['currency','Pricing'],['book','Resources']]) !!}
            </div>
        </header>
        {!! $body('Modern Interface', 'A Cleaner Way to Manage Enterprise Backups') !!}
        {!! $badge(5) !!}
    </div>

    {{-- ============================================================= --}}
    {{-- Design 06: Two-tone, saturated brand main band, white text    --}}
    {{-- ============================================================= --}}
    <div x-show="current === 5" x-cloak>
        <header x-data="{ open: false }">
            <div class="bg-navy text-slate-300">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-2 text-xs">
                    <a href="#" class="inline-flex items-center gap-1.5 hover:text-white">{!! $icon('clock', 'w-3.5 h-3.5') !!} Mon to Fri, 8 to 6 CT</a>
                    <div class="flex items-center gap-4">
                        <a href="#" class="hover:text-white">Docs</a>
                        <a href="#" class="hover:text-white">Support</a>
                        <a href="#" class="inline-flex items-center gap-1.5 font-semibold text-white">{!! $icon('user', 'w-3.5 h-3.5') !!} Sign In</a>
                    </div>
                </div>
            </div>
            <div class="bg-brand-700">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4">
                    {!! $logo('text-white', 'text-white') !!}
                    <nav class="hidden items-center gap-7 md:flex">
                        {!! $deskLink('cube', 'Products', 'text-brand-50 hover:text-white') !!}
                        {!! $deskLink('squares', 'Solutions', 'text-brand-50 hover:text-white') !!}
                        {!! $deskLink('currency', 'Pricing', 'text-brand-50 hover:text-white') !!}
                        {!! $deskLink('building', 'Company', 'text-brand-50 hover:text-white') !!}
                    </nav>
                    <div class="flex items-center gap-3">
                        <a href="#" class="hidden rounded-md bg-white px-4 py-2 text-sm font-semibold text-brand-700 hover:bg-brand-50 md:inline-flex">Request Demo</a>
                        <button type="button" class="inline-flex rounded-md p-2 text-white hover:bg-white/10 md:hidden" @click="open = !open" aria-label="Toggle Menu">{!! $icon('bars', 'w-6 h-6') !!}</button>
                    </div>
                </div>
                {!! $mobilePanel([['cube','Products'],['squares','Solutions'],['currency','Pricing'],['building','Company']]) !!}
            </div>
        </header>
        {!! $body('Brand Forward', 'Confident Colour, Uncompromised Reliability') !!}
        {!! $badge(6) !!}
    </div>

    {{-- ============================================================= --}}
    {{-- Design 07: Bordered / boxed navbar, hover boxes on links      --}}
    {{-- ============================================================= --}}
    <div x-show="current === 6" x-cloak>
        <header x-data="{ open: false }">
            <div class="bg-slate-900 text-slate-300">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-2 text-xs">
                    <div class="flex items-center gap-4">
                        <a href="#" class="inline-flex items-center gap-1.5 hover:text-white">{!! $icon('phone', 'w-3.5 h-3.5') !!} (555) 010 2200</a>
                        <a href="#" class="inline-flex items-center gap-1.5 hover:text-white">{!! $icon('envelope', 'w-3.5 h-3.5') !!} sales@scriptgain.com</a>
                    </div>
                    <a href="#" class="inline-flex items-center gap-1.5 font-semibold text-white">{!! $icon('user', 'w-3.5 h-3.5') !!} Sign In</a>
                </div>
            </div>
            <div class="border-y-2 border-slate-200 bg-white">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3">
                    {!! $logo() !!}
                    <nav class="hidden items-center gap-1 md:flex">
                        {!! $deskLink('cube', 'Products', 'text-slate-700 hover:text-brand-700 rounded-md px-3 py-2 ring-1 ring-transparent hover:ring-slate-200 hover:bg-slate-50') !!}
                        {!! $deskLink('squares', 'Solutions', 'text-slate-700 hover:text-brand-700 rounded-md px-3 py-2 ring-1 ring-transparent hover:ring-slate-200 hover:bg-slate-50') !!}
                        {!! $deskLink('currency', 'Pricing', 'text-slate-700 hover:text-brand-700 rounded-md px-3 py-2 ring-1 ring-transparent hover:ring-slate-200 hover:bg-slate-50') !!}
                        {!! $deskLink('book', 'Resources', 'text-slate-700 hover:text-brand-700 rounded-md px-3 py-2 ring-1 ring-transparent hover:ring-slate-200 hover:bg-slate-50') !!}
                    </nav>
                    <div class="flex items-center gap-3">
                        <a href="#" class="hidden rounded-md border-2 border-brand-600 bg-brand-600 px-4 py-1.5 text-sm font-semibold text-white hover:border-brand-700 hover:bg-brand-700 md:inline-flex">Contact Sales</a>
                        <button type="button" class="inline-flex rounded-md p-2 text-slate-600 hover:bg-slate-100 md:hidden" @click="open = !open" aria-label="Toggle Menu">{!! $icon('bars', 'w-6 h-6') !!}</button>
                    </div>
                </div>
                {!! $mobilePanel([['cube','Products'],['squares','Solutions'],['currency','Pricing'],['book','Resources']]) !!}
            </div>
        </header>
        {!! $body('Structured Layout', 'Precision Controls for Backup Operations') !!}
        {!! $badge(7) !!}
    </div>

    {{-- ============================================================= --}}
    {{-- Design 08: Condensed, uppercase tracked nav, minimal          --}}
    {{-- ============================================================= --}}
    <div x-show="current === 7" x-cloak>
        <header x-data="{ open: false }">
            <div class="bg-navy text-slate-400">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-1.5 text-[11px] uppercase tracking-wider">
                    <a href="#" class="hover:text-white">Enterprise Grade Data Protection</a>
                    <div class="flex items-center gap-4">
                        <a href="#" class="hover:text-white">Docs</a>
                        <a href="#" class="hover:text-white">Status</a>
                        <a href="#" class="inline-flex items-center gap-1.5 font-semibold text-white">{!! $icon('user', 'w-3 h-3') !!} Sign In</a>
                    </div>
                </div>
            </div>
            <div class="border-b border-slate-200 bg-white">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3">
                    {!! $logo() !!}
                    <nav class="hidden items-center gap-8 md:flex">
                        {!! $deskLink('cube', 'Products', 'text-slate-600 hover:text-brand-700 text-xs font-semibold uppercase tracking-widest', 'w-3.5 h-3.5') !!}
                        {!! $deskLink('squares', 'Solutions', 'text-slate-600 hover:text-brand-700 text-xs font-semibold uppercase tracking-widest', 'w-3.5 h-3.5') !!}
                        {!! $deskLink('currency', 'Pricing', 'text-slate-600 hover:text-brand-700 text-xs font-semibold uppercase tracking-widest', 'w-3.5 h-3.5') !!}
                        {!! $deskLink('building', 'Company', 'text-slate-600 hover:text-brand-700 text-xs font-semibold uppercase tracking-widest', 'w-3.5 h-3.5') !!}
                    </nav>
                    <div class="flex items-center gap-3">
                        <a href="#" class="hidden text-xs font-semibold uppercase tracking-widest text-brand-700 hover:text-brand-800 md:inline">Request Demo</a>
                        <button type="button" class="inline-flex rounded-md p-2 text-slate-600 hover:bg-slate-100 md:hidden" @click="open = !open" aria-label="Toggle Menu">{!! $icon('bars', 'w-6 h-6') !!}</button>
                    </div>
                </div>
                {!! $mobilePanel([['cube','Products'],['squares','Solutions'],['currency','Pricing'],['building','Company']]) !!}
            </div>
        </header>
        {!! $body('Minimal by Design', 'Quiet Confidence, Serious Recovery') !!}
        {!! $badge(8) !!}
    </div>

    {{-- ============================================================= --}}
    {{-- Design 09: Transparent over brand hero, light text            --}}
    {{-- ============================================================= --}}
    <div x-show="current === 8" x-cloak>
        <header x-data="{ open: false }" class="bg-gradient-to-br from-brand-900 via-brand-800 to-brand-700">
            <div class="border-b border-white/10 text-brand-100">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-2 text-xs">
                    <a href="#" class="inline-flex items-center gap-1.5 hover:text-white">{!! $icon('phone', 'w-3.5 h-3.5') !!} Talk to an Architect</a>
                    <div class="flex items-center gap-4">
                        <a href="#" class="inline-flex items-center gap-1.5 hover:text-white">{!! $icon('globe', 'w-3.5 h-3.5') !!} EN</a>
                        <a href="#" class="inline-flex items-center gap-1.5 font-semibold text-white">{!! $icon('user', 'w-3.5 h-3.5') !!} Sign In</a>
                    </div>
                </div>
            </div>
            <div>
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-5">
                    {!! $logo('text-white', 'text-white') !!}
                    <nav class="hidden items-center gap-8 md:flex">
                        {!! $deskLink('cube', 'Products', 'text-brand-50 hover:text-white') !!}
                        {!! $deskLink('squares', 'Solutions', 'text-brand-50 hover:text-white') !!}
                        {!! $deskLink('currency', 'Pricing', 'text-brand-50 hover:text-white') !!}
                        {!! $deskLink('book', 'Resources', 'text-brand-50 hover:text-white') !!}
                    </nav>
                    <div class="flex items-center gap-3">
                        <a href="#" class="hidden rounded-md bg-white px-4 py-2 text-sm font-semibold text-brand-800 hover:bg-brand-50 md:inline-flex">Get Started</a>
                        <button type="button" class="inline-flex rounded-md p-2 text-white hover:bg-white/10 md:hidden" @click="open = !open" aria-label="Toggle Menu">{!! $icon('bars', 'w-6 h-6') !!}</button>
                    </div>
                </div>
            </div>
            <div x-show="open" x-cloak class="md:hidden border-t border-white/10 px-4 py-3 space-y-1">
                @foreach ([['cube','Products'],['squares','Solutions'],['currency','Pricing'],['book','Resources']] as $l)
                    <a href="#" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-brand-50 hover:bg-white/10">{!! $icon($l[0], 'w-5 h-5') !!}<span>{{ $l[1] }}</span></a>
                @endforeach
            </div>
            <div class="mx-auto max-w-7xl px-4 pb-20 pt-10">
                <p class="text-xs font-semibold uppercase tracking-widest text-brand-200">Zero Downtime</p>
                <h1 class="mt-3 max-w-2xl text-3xl font-bold tracking-tight text-white sm:text-4xl">Recovery That Keeps Pace With Your Business</h1>
                <p class="mt-4 max-w-xl text-brand-100">Backups that never block production and restores measured in minutes across your entire estate.</p>
            </div>
        </header>
        {!! $body('Below the Fold', 'The Platform Behind the Promise') !!}
        {!! $badge(9) !!}
    </div>

    {{-- ============================================================= --}}
    {{-- Design 10: Split nav with divider, ghost + solid dual CTA     --}}
    {{-- ============================================================= --}}
    <div x-show="current === 9" x-cloak>
        <header x-data="{ open: false }">
            <div class="bg-navy text-slate-300">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-2 text-xs">
                    <div class="flex items-center gap-4">
                        <a href="#" class="hover:text-white">For Enterprise</a>
                        <a href="#" class="hover:text-white">For MSPs</a>
                        <a href="#" class="hover:text-white">For Government</a>
                    </div>
                    <a href="#" class="inline-flex items-center gap-1.5 font-semibold text-white">{!! $icon('user', 'w-3.5 h-3.5') !!} Partner Portal</a>
                </div>
            </div>
            <div class="border-b border-slate-200 bg-white">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4">
                    <div class="flex items-center gap-6">
                        {!! $logo() !!}
                        <span class="hidden h-6 w-px bg-slate-200 md:block"></span>
                        <nav class="hidden items-center gap-6 md:flex">
                            {!! $deskLink('cube', 'Products') !!}
                            {!! $deskLink('squares', 'Solutions') !!}
                            {!! $deskLink('currency', 'Pricing') !!}
                            {!! $deskLink('building', 'Company') !!}
                        </nav>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="#" class="hidden rounded-md px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 md:inline-flex">Sign In</a>
                        <a href="#" class="hidden rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 md:inline-flex">Request Demo</a>
                        <button type="button" class="inline-flex rounded-md p-2 text-slate-600 hover:bg-slate-100 md:hidden" @click="open = !open" aria-label="Toggle Menu">{!! $icon('bars', 'w-6 h-6') !!}</button>
                    </div>
                </div>
                {!! $mobilePanel([['cube','Products'],['squares','Solutions'],['currency','Pricing'],['building','Company']]) !!}
            </div>
        </header>
        {!! $body('Segmented Solutions', 'Purpose Built for Every Kind of Team') !!}
        {!! $badge(10) !!}
    </div>

    {{-- ============================================================= --}}
    {{-- Design 11: Right-aligned nav, language dropdown in bar        --}}
    {{-- ============================================================= --}}
    <div x-show="current === 10" x-cloak>
        <header x-data="{ open: false, lang: false }">
            <div class="bg-slate-900 text-slate-300">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-2 text-xs">
                    <a href="#" class="inline-flex items-center gap-1.5 hover:text-white">{!! $icon('phone', 'w-3.5 h-3.5') !!} (555) 010 2200</a>
                    <div class="flex items-center gap-4">
                        <div class="relative">
                            <button type="button" @click="lang = !lang" class="inline-flex items-center gap-1.5 hover:text-white">{!! $icon('globe', 'w-3.5 h-3.5') !!} English {!! $icon('chevron', 'w-3 h-3') !!}</button>
                            <div x-show="lang" x-cloak @click.outside="lang = false" class="absolute right-0 top-full z-30 mt-1 w-32 rounded-md border border-slate-200 bg-white py-1 text-slate-700 shadow-lg">
                                <a href="#" class="block px-3 py-1.5 text-xs hover:bg-slate-50">English</a>
                                <a href="#" class="block px-3 py-1.5 text-xs hover:bg-slate-50">Deutsch</a>
                                <a href="#" class="block px-3 py-1.5 text-xs hover:bg-slate-50">Francais</a>
                            </div>
                        </div>
                        <a href="#" class="inline-flex items-center gap-1.5 font-semibold text-white">{!! $icon('user', 'w-3.5 h-3.5') !!} Sign In</a>
                    </div>
                </div>
            </div>
            <div class="border-b border-slate-200 bg-white">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4">
                    {!! $logo() !!}
                    <div class="flex items-center gap-7">
                        <nav class="hidden items-center gap-7 md:flex">
                            {!! $deskLink('cube', 'Products') !!}
                            {!! $deskLink('squares', 'Solutions') !!}
                            {!! $deskLink('currency', 'Pricing') !!}
                            {!! $deskLink('book', 'Resources') !!}
                        </nav>
                        <a href="#" class="hidden items-center gap-2 rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 md:inline-flex">{!! $icon('rocket', 'w-4 h-4') !!} Get Started</a>
                        <button type="button" class="inline-flex rounded-md p-2 text-slate-600 hover:bg-slate-100 md:hidden" @click="open = !open" aria-label="Toggle Menu">{!! $icon('bars', 'w-6 h-6') !!}</button>
                    </div>
                </div>
                {!! $mobilePanel([['cube','Products'],['squares','Solutions'],['currency','Pricing'],['book','Resources']]) !!}
            </div>
        </header>
        {!! $body('Global Ready', 'Localized Everywhere You Operate') !!}
        {!! $badge(11) !!}
    </div>

    {{-- ============================================================= --}}
    {{-- Design 12: Mega grid dropdown under Solutions                 --}}
    {{-- ============================================================= --}}
    <div x-show="current === 11" x-cloak>
        <header x-data="{ open: false, sol: false }">
            <div class="bg-navy text-slate-300">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-2 text-xs">
                    <span class="inline-flex items-center gap-2 rounded-full bg-brand-500/15 px-2.5 py-1 font-medium text-brand-200 ring-1 ring-brand-400/30">{!! $icon('sparkles', 'w-3.5 h-3.5') !!} New: AI Restore Assist</span>
                    <div class="flex items-center gap-4">
                        <a href="#" class="hover:text-white">Support</a>
                        <a href="#" class="inline-flex items-center gap-1.5 font-semibold text-white">{!! $icon('user', 'w-3.5 h-3.5') !!} Sign In</a>
                    </div>
                </div>
            </div>
            <div class="relative border-b border-slate-200 bg-white">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4">
                    {!! $logo() !!}
                    <nav class="hidden items-center gap-7 md:flex">
                        {!! $deskLink('cube', 'Products') !!}
                        <button type="button" @click="sol = !sol" class="inline-flex items-center gap-2 text-sm font-medium text-slate-700 hover:text-brand-700">{!! $icon('squares', 'w-4 h-4') !!} Solutions {!! $icon('chevron', 'w-4 h-4') !!}</button>
                        {!! $deskLink('currency', 'Pricing') !!}
                        {!! $deskLink('building', 'Company') !!}
                    </nav>
                    <div class="flex items-center gap-3">
                        <a href="#" class="hidden rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 md:inline-flex">Request Demo</a>
                        <button type="button" class="inline-flex rounded-md p-2 text-slate-600 hover:bg-slate-100 md:hidden" @click="open = !open" aria-label="Toggle Menu">{!! $icon('bars', 'w-6 h-6') !!}</button>
                    </div>
                </div>
                <div x-show="sol" x-cloak @click.outside="sol = false" class="absolute inset-x-0 top-full z-30 hidden border-b border-slate-200 bg-white shadow-lg md:block">
                    <div class="mx-auto grid max-w-7xl grid-cols-4 gap-4 px-4 py-8">
                        @foreach ([['building','Financial Services'],['shield','Healthcare'],['academic','Education'],['pin','Public Sector'],['server','Data Centers'],['globe','Multi Cloud'],['users','Managed Providers'],['briefcase','Legal']] as $s)
                            <a href="#" class="flex items-center gap-3 rounded-lg p-3 hover:bg-slate-50">
                                <span class="text-brand-600">{!! $icon($s[0], 'w-5 h-5') !!}</span>
                                <span class="text-sm font-medium text-slate-800">{{ $s[1] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
                {!! $mobilePanel([['cube','Products'],['squares','Solutions'],['currency','Pricing'],['building','Company']]) !!}
            </div>
        </header>
        {!! $body('By Industry', 'Compliance Ready Backup for Regulated Sectors') !!}
        {!! $badge(12) !!}
    </div>

    {{-- ============================================================= --}}
    {{-- Design 13: Fully dark two-tone header, brand CTA              --}}
    {{-- ============================================================= --}}
    <div x-show="current === 12" x-cloak>
        <header x-data="{ open: false }">
            <div class="bg-black text-slate-400">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-2 text-xs">
                    <a href="#" class="inline-flex items-center gap-1.5 hover:text-white">{!! $icon('envelope', 'w-3.5 h-3.5') !!} sales@scriptgain.com</a>
                    <div class="flex items-center gap-4">
                        <a href="#" class="hover:text-white">Docs</a>
                        <a href="#" class="hover:text-white">Status</a>
                        <a href="#" class="inline-flex items-center gap-1.5 font-semibold text-white">{!! $icon('user', 'w-3.5 h-3.5') !!} Sign In</a>
                    </div>
                </div>
            </div>
            <div class="border-b border-white/10 bg-navy">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4">
                    {!! $logo('text-white', 'text-brand-400') !!}
                    <nav class="hidden items-center gap-7 md:flex">
                        {!! $deskLink('cube', 'Products', 'text-slate-300 hover:text-white') !!}
                        {!! $deskLink('squares', 'Solutions', 'text-slate-300 hover:text-white') !!}
                        {!! $deskLink('currency', 'Pricing', 'text-slate-300 hover:text-white') !!}
                        {!! $deskLink('book', 'Resources', 'text-slate-300 hover:text-white') !!}
                    </nav>
                    <div class="flex items-center gap-3">
                        <a href="#" class="hidden rounded-md bg-brand-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-brand-400 md:inline-flex">Request Demo</a>
                        <button type="button" class="inline-flex rounded-md p-2 text-slate-300 hover:bg-white/10 md:hidden" @click="open = !open" aria-label="Toggle Menu">{!! $icon('bars', 'w-6 h-6') !!}</button>
                    </div>
                </div>
                <div x-show="open" x-cloak class="md:hidden border-t border-white/10 px-4 py-3 space-y-1">
                    @foreach ([['cube','Products'],['squares','Solutions'],['currency','Pricing'],['book','Resources']] as $l)
                        <a href="#" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-slate-300 hover:bg-white/10">{!! $icon($l[0], 'w-5 h-5') !!}<span>{{ $l[1] }}</span></a>
                    @endforeach
                </div>
            </div>
        </header>
        {!! $body('Command Center', 'A Dark, Focused Console for Operators') !!}
        {!! $badge(13) !!}
    </div>

    {{-- ============================================================= --}}
    {{-- Design 14: White navbar, thick brand active underline, accent --}}
    {{-- ============================================================= --}}
    <div x-show="current === 13" x-cloak>
        <header x-data="{ open: false }">
            <div class="bg-brand-950 text-brand-100">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-2 text-xs">
                    <div class="flex items-center gap-4">
                        <a href="#" class="inline-flex items-center gap-1.5 hover:text-white">{!! $icon('phone', 'w-3.5 h-3.5') !!} (555) 010 2200</a>
                        <a href="#" class="hidden items-center gap-1.5 hover:text-white sm:inline-flex">{!! $icon('clock', 'w-3.5 h-3.5') !!} 24/7 Support</a>
                    </div>
                    <a href="#" class="inline-flex items-center gap-1.5 font-semibold text-white">{!! $icon('user', 'w-3.5 h-3.5') !!} Sign In</a>
                </div>
            </div>
            <div class="bg-white shadow-sm">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4">
                    {!! $logo() !!}
                    <nav class="hidden items-center gap-8 md:flex">
                        <a href="#" class="relative inline-flex items-center gap-2 pb-4 -mb-4 text-sm font-semibold text-slate-900">{!! $icon('cube', 'w-4 h-4') !!} Products <span class="absolute inset-x-0 bottom-0 h-1 rounded-t bg-brand-600"></span></a>
                        {!! $deskLink('squares', 'Solutions', 'text-slate-600 hover:text-slate-900') !!}
                        {!! $deskLink('currency', 'Pricing', 'text-slate-600 hover:text-slate-900') !!}
                        {!! $deskLink('building', 'Company', 'text-slate-600 hover:text-slate-900') !!}
                    </nav>
                    <div class="flex items-center gap-3">
                        <a href="#" class="hidden rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 md:inline-flex">Get Started</a>
                        <button type="button" class="inline-flex rounded-md p-2 text-slate-600 hover:bg-slate-100 md:hidden" @click="open = !open" aria-label="Toggle Menu">{!! $icon('bars', 'w-6 h-6') !!}</button>
                    </div>
                </div>
                {!! $mobilePanel([['cube','Products'],['squares','Solutions'],['currency','Pricing'],['building','Company']]) !!}
            </div>
        </header>
        {!! $body('Always On', 'Support and Recovery, Around the Clock') !!}
        {!! $badge(14) !!}
    </div>

    {{-- ============================================================= --}}
    {{-- Design 15: Wide utility quick-links row, large icon nav       --}}
    {{-- ============================================================= --}}
    <div x-show="current === 14" x-cloak>
        <header x-data="{ open: false }">
            <div class="bg-navy text-slate-300">
                <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2 px-4 py-2 text-xs">
                    <div class="flex flex-wrap items-center gap-4">
                        <a href="#" class="inline-flex items-center gap-1.5 hover:text-white">{!! $icon('phone', 'w-3.5 h-3.5') !!} (555) 010 2200</a>
                        <a href="#" class="inline-flex items-center gap-1.5 hover:text-white">{!! $icon('envelope', 'w-3.5 h-3.5') !!} sales@scriptgain.com</a>
                        <a href="#" class="inline-flex items-center gap-1.5 hover:text-white">{!! $icon('pin', 'w-3.5 h-3.5') !!} Austin, Texas</a>
                    </div>
                    <div class="flex flex-wrap items-center gap-4">
                        <a href="#" class="hover:text-white">Support</a>
                        <a href="#" class="hover:text-white">Docs</a>
                        <a href="#" class="hover:text-white">Partner Portal</a>
                        <a href="#" class="inline-flex items-center gap-1.5 font-semibold text-white">{!! $icon('user', 'w-3.5 h-3.5') !!} Sign In</a>
                    </div>
                </div>
            </div>
            <div class="border-b border-slate-200 bg-white">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-5">
                    {!! $logo() !!}
                    <nav class="hidden items-center gap-8 md:flex">
                        {!! $deskLink('cube', 'Products', 'text-slate-700 hover:text-brand-700 text-[15px]', 'w-5 h-5') !!}
                        {!! $deskLink('squares', 'Solutions', 'text-slate-700 hover:text-brand-700 text-[15px]', 'w-5 h-5') !!}
                        {!! $deskLink('currency', 'Pricing', 'text-slate-700 hover:text-brand-700 text-[15px]', 'w-5 h-5') !!}
                        {!! $deskLink('book', 'Resources', 'text-slate-700 hover:text-brand-700 text-[15px]', 'w-5 h-5') !!}
                    </nav>
                    <div class="flex items-center gap-3">
                        <a href="#" class="hidden rounded-md bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700 md:inline-flex">Request Demo</a>
                        <button type="button" class="inline-flex rounded-md p-2 text-slate-600 hover:bg-slate-100 md:hidden" @click="open = !open" aria-label="Toggle Menu">{!! $icon('bars', 'w-6 h-6') !!}</button>
                    </div>
                </div>
                {!! $mobilePanel([['cube','Products'],['squares','Solutions'],['currency','Pricing'],['book','Resources']]) !!}
            </div>
        </header>
        {!! $body('Everything In Reach', 'Every Resource, One Click Away') !!}
        {!! $badge(15) !!}
    </div>

    {{-- ============================================================= --}}
    {{-- Design 16: Nav links with New badges                          --}}
    {{-- ============================================================= --}}
    <div x-show="current === 15" x-cloak>
        <header x-data="{ open: false }">
            <div class="bg-slate-900 text-slate-300">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-2 text-xs">
                    <a href="#" class="inline-flex items-center gap-1.5 hover:text-white">{!! $icon('bolt', 'w-3.5 h-3.5') !!} Now with AI Anomaly Detection</a>
                    <div class="flex items-center gap-4">
                        <a href="#" class="hover:text-white">Support</a>
                        <a href="#" class="inline-flex items-center gap-1.5 font-semibold text-white">{!! $icon('user', 'w-3.5 h-3.5') !!} Sign In</a>
                    </div>
                </div>
            </div>
            <div class="border-b border-slate-200 bg-white">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4">
                    {!! $logo() !!}
                    <nav class="hidden items-center gap-7 md:flex">
                        {!! $deskLink('cube', 'Products') !!}
                        <a href="#" class="inline-flex items-center gap-2 text-sm font-medium text-slate-700 hover:text-brand-700">{!! $icon('sparkles', 'w-4 h-4') !!} Platform <span class="rounded bg-brand-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-brand-700">New</span></a>
                        {!! $deskLink('currency', 'Pricing') !!}
                        {!! $deskLink('building', 'Company') !!}
                    </nav>
                    <div class="flex items-center gap-3">
                        <a href="#" class="hidden rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 md:inline-flex">Request Demo</a>
                        <button type="button" class="inline-flex rounded-md p-2 text-slate-600 hover:bg-slate-100 md:hidden" @click="open = !open" aria-label="Toggle Menu">{!! $icon('bars', 'w-6 h-6') !!}</button>
                    </div>
                </div>
                {!! $mobilePanel([['cube','Products'],['sparkles','Platform'],['currency','Pricing'],['building','Company']]) !!}
            </div>
        </header>
        {!! $body('Whats New', 'Fresh Capabilities, Same Trusted Core') !!}
        {!! $badge(16) !!}
    </div>

    {{-- ============================================================= --}}
    {{-- Design 17: ServiceNow style, thin borders, uppercase minimal  --}}
    {{-- ============================================================= --}}
    <div x-show="current === 16" x-cloak>
        <header x-data="{ open: false }">
            <div class="bg-navy text-slate-400">
                <div class="mx-auto flex max-w-7xl items-center justify-end gap-5 px-4 py-1.5 text-[11px] font-medium uppercase tracking-wide">
                    <a href="#" class="hover:text-white">Customers</a>
                    <a href="#" class="hover:text-white">Partners</a>
                    <a href="#" class="hover:text-white">Developers</a>
                    <a href="#" class="inline-flex items-center gap-1.5 text-white">{!! $icon('user', 'w-3 h-3') !!} Sign In</a>
                </div>
            </div>
            <div class="border-b border-slate-200 bg-white">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4">
                    <div class="flex items-center gap-10">
                        {!! $logo() !!}
                        <nav class="hidden items-center gap-8 lg:flex">
                            {!! $deskLink('cube', 'Products', 'text-slate-800 hover:text-brand-700') !!}
                            {!! $deskLink('squares', 'Solutions', 'text-slate-800 hover:text-brand-700') !!}
                            {!! $deskLink('academic', 'Learn', 'text-slate-800 hover:text-brand-700') !!}
                            {!! $deskLink('building', 'Company', 'text-slate-800 hover:text-brand-700') !!}
                        </nav>
                    </div>
                    <div class="flex items-center gap-4">
                        <a href="#" class="hidden text-sm font-semibold text-slate-700 hover:text-brand-700 lg:inline">Contact Sales</a>
                        <a href="#" class="hidden rounded-sm bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 md:inline-flex">Get Started</a>
                        <button type="button" class="inline-flex rounded-md p-2 text-slate-600 hover:bg-slate-100 md:hidden" @click="open = !open" aria-label="Toggle Menu">{!! $icon('bars', 'w-6 h-6') !!}</button>
                    </div>
                </div>
                {!! $mobilePanel([['cube','Products'],['squares','Solutions'],['academic','Learn'],['building','Company']]) !!}
            </div>
        </header>
        {!! $body('Enterprise Standard', 'The Platform Fortune 500s Recover On') !!}
        {!! $badge(17) !!}
    </div>

    {{-- ============================================================= --}}
    {{-- Design 18: Prominent phone number CTA cluster in navbar       --}}
    {{-- ============================================================= --}}
    <div x-show="current === 17" x-cloak>
        <header x-data="{ open: false }">
            <div class="bg-brand-900 text-brand-100">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-2 text-xs">
                    <a href="#" class="hover:text-white">Trusted by 2,400 Enterprises Worldwide</a>
                    <div class="flex items-center gap-4">
                        <a href="#" class="hover:text-white">Docs</a>
                        <a href="#" class="inline-flex items-center gap-1.5 font-semibold text-white">{!! $icon('user', 'w-3.5 h-3.5') !!} Sign In</a>
                    </div>
                </div>
            </div>
            <div class="border-b border-slate-200 bg-white">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4">
                    {!! $logo() !!}
                    <nav class="hidden items-center gap-7 md:flex">
                        {!! $deskLink('cube', 'Products') !!}
                        {!! $deskLink('squares', 'Solutions') !!}
                        {!! $deskLink('currency', 'Pricing') !!}
                        {!! $deskLink('book', 'Resources') !!}
                    </nav>
                    <div class="flex items-center gap-4">
                        <a href="#" class="hidden items-center gap-2 text-right md:inline-flex">
                            <span class="text-brand-600">{!! $icon('phone-arrow', 'w-5 h-5') !!}</span>
                            <span class="leading-tight"><span class="block text-[11px] font-medium text-slate-500">Call Sales</span><span class="block text-sm font-bold text-slate-900">(555) 010 2200</span></span>
                        </a>
                        <a href="#" class="hidden rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 md:inline-flex">Request Demo</a>
                        <button type="button" class="inline-flex rounded-md p-2 text-slate-600 hover:bg-slate-100 md:hidden" @click="open = !open" aria-label="Toggle Menu">{!! $icon('bars', 'w-6 h-6') !!}</button>
                    </div>
                </div>
                {!! $mobilePanel([['cube','Products'],['squares','Solutions'],['currency','Pricing'],['book','Resources']]) !!}
            </div>
        </header>
        {!! $body('Talk to a Human', 'Enterprise Sales, Ready When You Are') !!}
        {!! $badge(18) !!}
    </div>

    {{-- ============================================================= --}}
    {{-- Design 19: Expandable search icon, center nav                 --}}
    {{-- ============================================================= --}}
    <div x-show="current === 18" x-cloak>
        <header x-data="{ open: false, search: false }">
            <div class="bg-navy text-slate-300">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-2 text-xs">
                    <a href="#" class="inline-flex items-center gap-1.5 hover:text-white">{!! $icon('shield', 'w-3.5 h-3.5') !!} SOC 2 and ISO 27001 Certified</a>
                    <div class="flex items-center gap-4">
                        <a href="#" class="hover:text-white">Support</a>
                        <a href="#" class="inline-flex items-center gap-1.5 font-semibold text-white">{!! $icon('user', 'w-3.5 h-3.5') !!} Sign In</a>
                    </div>
                </div>
            </div>
            <div class="border-b border-slate-200 bg-white">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4">
                    {!! $logo() !!}
                    <nav class="hidden items-center gap-7 md:flex" x-show="!search">
                        {!! $deskLink('cube', 'Products') !!}
                        {!! $deskLink('squares', 'Solutions') !!}
                        {!! $deskLink('currency', 'Pricing') !!}
                        {!! $deskLink('building', 'Company') !!}
                    </nav>
                    <div x-show="search" x-cloak class="mx-6 hidden flex-1 items-center rounded-md bg-slate-100 px-3 py-2 md:flex">
                        <span class="text-slate-400">{!! $icon('search', 'w-4 h-4') !!}</span>
                        <input type="text" placeholder="Search the platform" class="w-full border-0 bg-transparent px-2 text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none">
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="button" @click="search = !search" class="hidden rounded-md p-2 text-slate-600 hover:bg-slate-100 md:inline-flex" aria-label="Toggle Search">{!! $icon('search', 'w-5 h-5') !!}</button>
                        <a href="#" class="hidden rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 md:inline-flex">Get Started</a>
                        <button type="button" class="inline-flex rounded-md p-2 text-slate-600 hover:bg-slate-100 md:hidden" @click="open = !open" aria-label="Toggle Menu">{!! $icon('bars', 'w-6 h-6') !!}</button>
                    </div>
                </div>
                {!! $mobilePanel([['cube','Products'],['squares','Solutions'],['currency','Pricing'],['building','Company']]) !!}
            </div>
        </header>
        {!! $body('Secure by Default', 'Certified Protection You Can Audit') !!}
        {!! $badge(19) !!}
    </div>

    {{-- ============================================================= --}}
    {{-- Design 20: Gradient utility bar, floating white nav card      --}}
    {{-- ============================================================= --}}
    <div x-show="current === 19" x-cloak>
        <header x-data="{ open: false }" class="bg-slate-50">
            <div class="bg-gradient-to-r from-brand-800 via-brand-700 to-brand-600 text-white">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-2 text-xs">
                    <div class="flex items-center gap-4">
                        <a href="#" class="inline-flex items-center gap-1.5 hover:text-brand-100">{!! $icon('phone', 'w-3.5 h-3.5') !!} (555) 010 2200</a>
                        <a href="#" class="hidden items-center gap-1.5 hover:text-brand-100 sm:inline-flex">{!! $icon('envelope', 'w-3.5 h-3.5') !!} sales@scriptgain.com</a>
                    </div>
                    <div class="flex items-center gap-4">
                        <a href="#" class="hover:text-brand-100">Docs</a>
                        <a href="#" class="inline-flex items-center gap-1.5 font-semibold">{!! $icon('user', 'w-3.5 h-3.5') !!} Sign In</a>
                    </div>
                </div>
            </div>
            <div class="mx-auto max-w-7xl px-4 pt-4">
                <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-5 py-3 shadow-sm">
                    {!! $logo() !!}
                    <nav class="hidden items-center gap-7 md:flex">
                        {!! $deskLink('cube', 'Products') !!}
                        {!! $deskLink('squares', 'Solutions') !!}
                        {!! $deskLink('currency', 'Pricing') !!}
                        {!! $deskLink('book', 'Resources') !!}
                    </nav>
                    <div class="flex items-center gap-3">
                        <a href="#" class="hidden rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 md:inline-flex">Request Demo</a>
                        <button type="button" class="inline-flex rounded-md p-2 text-slate-600 hover:bg-slate-100 md:hidden" @click="open = !open" aria-label="Toggle Menu">{!! $icon('bars', 'w-6 h-6') !!}</button>
                    </div>
                </div>
                <div x-show="open" x-cloak class="md:hidden mt-2 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm space-y-1">
                    @foreach ([['cube','Products'],['squares','Solutions'],['currency','Pricing'],['book','Resources']] as $l)
                        <a href="#" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">{!! $icon($l[0], 'w-5 h-5 text-slate-500') !!}<span>{{ $l[1] }}</span></a>
                    @endforeach
                </div>
            </div>
        </header>
        {!! $body('Elevated', 'A Floating Nav for a Modern Estate') !!}
        {!! $badge(20) !!}
    </div>

</body>
</html>
