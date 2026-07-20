<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50 scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Documentation — {{ config('brand.name') }}</title>
    <x-tailwind-cdn />
    <style>
        .doc-prose p{color:#475569;line-height:1.7}
        .doc-prose strong{color:#0f172a;font-weight:600}
        .doc-prose a{color:var(--color-brand-700,#1d4ed8);text-decoration:underline;text-underline-offset:2px}
        .doc-prose code:not(pre code){background:#eef2f7;color:#0f172a;border-radius:.3rem;padding:.05rem .35rem;font-size:.85em}
        .doc-prose ul{margin-top:.25rem}
        mark{background:#fde68a;color:inherit;border-radius:.2rem;padding:0 .1rem}
        /* Left navigation — carded, with an active accent rail + icon chips. */
        .docnav{position:sticky;top:6rem}
        .docnav-card{border:1px solid #e2e8f0;border-radius:1rem;background:#fff;box-shadow:0 1px 2px rgba(2,6,23,.05);padding:.5rem}
        .docnav-title{font-size:.66rem;letter-spacing:.09em;text-transform:uppercase;color:#94a3b8;font-weight:700;padding:.55rem .75rem .35rem}
        .nav-link{position:relative;display:flex;align-items:center;gap:.6rem;border-radius:.7rem;padding:.5rem .7rem .5rem .85rem;color:#475569;font-weight:500;transition:background .15s ease,color .15s ease}
        .nav-link .n-ico{display:inline-flex;height:1.65rem;width:1.65rem;flex:none;align-items:center;justify-content:center;border-radius:.5rem;background:#f1f5f9;color:#94a3b8;transition:all .15s ease}
        .nav-link:hover{background:#f8fafc;color:#0f172a}
        .nav-link:hover .n-ico{color:#475569;background:#e2e8f0}
        .nav-link.active{background:color-mix(in srgb, var(--color-brand-600,#2563eb) 9%, transparent);color:var(--color-brand-800,#1e40af);font-weight:600}
        .nav-link.active .n-ico{background:var(--color-brand-600,#2563eb);color:#fff;box-shadow:0 2px 8px color-mix(in srgb,var(--color-brand-600,#2563eb) 45%,transparent)}
        .nav-link.active::before{content:"";position:absolute;left:.28rem;top:.45rem;bottom:.45rem;width:3px;border-radius:9px;background:var(--color-brand-600,#2563eb)}
        .docnav-back{display:flex;align-items:center;gap:.4rem;padding:.5rem .85rem;margin-top:.5rem;font-size:.78rem;color:#94a3b8;transition:color .15s ease}
        .docnav-back:hover{color:var(--color-brand-700,#1d4ed8)}
        /* Documentation panels: header / body / footer. */
        .doc-panel{border:1px solid #e2e8f0;border-radius:1rem;background:#fff;box-shadow:0 1px 2px rgba(2,6,23,.05);overflow:hidden}
        .panel-head{display:flex;align-items:center;gap:.75rem;padding:.8rem 1.25rem;background:#f8fafc;border-bottom:1px solid #e2e8f0}
        .panel-head h2{font-size:1.05rem;font-weight:600;color:#0f172a}
        .panel-head .chip{display:inline-flex;height:2rem;width:2rem;align-items:center;justify-content:center;border-radius:.6rem;background:var(--color-brand-50,#eff6ff);color:var(--color-brand-700,#1d4ed8);box-shadow:inset 0 0 0 1px var(--color-brand-100,#dbeafe)}
        .panel-body{padding:1.25rem}
        .panel-body>*+*{margin-top:.75rem}
        .panel-foot{display:flex;align-items:center;justify-content:space-between;gap:.5rem;padding:.6rem 1.25rem;background:#f8fafc;border-top:1px solid #e2e8f0;font-size:.75rem;color:#94a3b8}
        .panel-foot a{color:var(--color-brand-700,#1d4ed8)}
    </style>
</head>
@php
    $host = rtrim(config('app.url'), '/');
    $ver  = \App\Services\UpdateService::currentVersion();
    $sections = [
        ['overview',     'Overview',               'M4 6h16M4 12h16M4 18h7'],
        ['master',       'Install the Manager',    'M5 12h14M12 5v14'],
        ['agents',       'Enroll an Agent',        'M12 4v16m8-8H4'],
        ['connectors',   'Backup Connectors',      'M13 10V3L4 14h7v7l9-11h-7z'],
        ['repositories', 'Repositories',           'M4 7v10a2 2 0 002 2h12a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H6a2 2 0 00-2 2z'],
        ['schedule',     'Scheduling & Retention', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['updates',      'Updates',                'M4 4v6h6M20 20v-6h-6M20 8A8 8 0 006 5M4 16a8 8 0 0014 3'],
        ['license',      'Licensing',              'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.7 5.7L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.6a1 1 0 01.3-.7l6-6A6 6 0 1121 9z'],
    ];
@endphp
<body class="min-h-full text-slate-800">

{{-- Top bar with search --}}
<header id="top" class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur">
    <div class="mx-auto max-w-6xl px-4 h-14 flex items-center gap-4">
        <a href="{{ url('/') }}" class="flex items-center gap-2 font-semibold text-slate-900 shrink-0">
            <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-brand-600 text-white text-sm">◈</span>
            {{ config('brand.name') }}
        </a>
        <span class="hidden sm:inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500 tabular">v{{ $ver }} · Docs</span>
        <div class="ml-auto relative w-full max-w-xs">
            <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/></svg>
            <input id="docsearch" type="search" autocomplete="off" placeholder="Search the docs…  ( / )"
                class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2 pl-9 pr-8 text-sm text-slate-800 placeholder-slate-400 focus:border-brand-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-200">
            <button id="docsearch-clear" type="button" class="absolute right-2 top-1/2 -translate-y-1/2 hidden h-5 w-5 items-center justify-center rounded text-slate-400 hover:text-slate-700">✕</button>
        </div>
    </div>
</header>

<div class="mx-auto max-w-6xl px-4 py-10 lg:grid lg:grid-cols-[220px_1fr] lg:gap-12">
    {{-- Sidebar --}}
    <aside class="hidden lg:block">
        <div class="docnav">
            <div class="docnav-card">
                <p class="docnav-title">On This Page</p>
                <nav id="docnav" class="flex flex-col gap-0.5 text-sm">
                    @foreach ($sections as [$id, $label, $d])
                        <a href="#{{ $id }}" data-nav="{{ $id }}" class="nav-link">
                            <span class="n-ico"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $d }}"/></svg></span>
                            <span>{{ $label }}</span>
                        </a>
                    @endforeach
                </nav>
            </div>
            <a href="{{ url('/') }}" class="docnav-back">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7 7-7M3 12h18"/></svg>
                Back to {{ config('brand.name') }}
            </a>
        </div>
    </aside>

    <main class="min-w-0">
        {{-- Hero --}}
        <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-br from-brand-600 to-brand-800 px-6 py-8 text-white shadow-sm">
            <div class="absolute -right-8 -top-10 h-40 w-40 rounded-full bg-white/10"></div>
            <div class="absolute -bottom-12 right-16 h-28 w-28 rounded-full bg-white/5"></div>
            <h1 class="relative text-3xl font-bold">{{ config('brand.name') }} Documentation</h1>
            <p class="relative mt-2 max-w-2xl text-white/85">Self-hosted backup control plane: a Manager you install once, and lightweight agents you drop onto each host you want to protect. Snapshots are deduplicated, encrypted, and pruned automatically.</p>
            <div class="relative mt-4 flex flex-wrap gap-2 text-xs">
                <span class="rounded-full bg-white/15 px-2.5 py-1">Linux x86_64</span>
                <span class="rounded-full bg-white/15 px-2.5 py-1">Ubuntu 22.04+ · Debian 12+</span>
                <span class="rounded-full bg-white/15 px-2.5 py-1">kopia-backed</span>
            </div>
        </div>

        @php
            $open = fn ($id, $title, $d) =>
                '<section data-doc id="sec-'.$id.'" class="doc-panel">'
                .'<div class="panel-head"><span class="chip"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="'.$d.'"/></svg></span>'
                .'<h2 id="'.$id.'" class="scroll-mt-24"><a href="#'.$id.'" class="hover:text-brand-700">'.$title.'</a></h2></div>'
                .'<div class="panel-body doc-prose">';
            $close = fn ($foot) =>
                '</div><div class="panel-foot"><span>'.$foot.'</span><a href="#top">Back to top ↑</a></div></section>';
            $code = fn ($c) =>
                '<div class="group relative">'
                .'<button type="button" class="copy-btn absolute right-2 top-2 rounded-md bg-white/10 px-2 py-1 text-xs text-slate-300 opacity-0 transition hover:bg-white/20 hover:text-white group-hover:opacity-100">Copy</button>'
                .'<pre class="overflow-x-auto rounded-xl bg-slate-900 px-4 py-3 text-sm text-slate-100"><code>'.$c.'</code></pre></div>';
        @endphp

        <div class="mt-8 space-y-6">
            {!! $open('overview', 'Overview', $sections[0][2]) !!}
                <p>The <strong>Manager</strong> queues jobs and stores backups; it never connects to your hosts. <strong>Agents</strong> poll the Manager over outbound HTTPS and do the work — either backing up their own host (<em>agent</em> connector) or acting as a <em>gateway</em> that pulls a remote host over SSH/FTP (<em>agentless</em> connectors). Snapshots are written with a bundled <a href="https://kopia.io">kopia</a> into per-host repositories.</p>
                <p>Supported OS: Linux x86_64 (Ubuntu 22.04+, Debian 12+).</p>
            {!! $close('The big picture') !!}

            {!! $open('master', 'Install the Manager', $sections[1][2]) !!}
                <p>On a fresh Ubuntu 22.04+/Debian 12 server, clone the repo and run the installer. It provisions PHP, MariaDB, nginx, the app, a queue worker + scheduler, and (with <code>SSL=1</code>) a Let's Encrypt certificate.</p>
                {!! $code("git clone https://github.com/scriptgain/backup-mgr.git\ncd backup-mgr\nsudo DOMAIN=backup.example.com SSL=1 EMAIL=you@example.com \\\n  LICENSE_KEY=XXXX-XXXX-XXXX-XXXX ./deploy/install-master.sh") !!}
                <p>Point DNS at the server first so the certificate can be issued. After install, create your admin user and log in. A default <em>Local Director</em> and a <em>Local Backups</em> repository are provisioned automatically.</p>
            {!! $close('One-time setup') !!}

            {!! $open('agents', 'Enroll an Agent', $sections[2][2]) !!}
                <p>In the Manager, add a Host (type <em>Agent</em>) to get a one-time enrollment token. Then, on the host you want to back up:</p>
                {!! $code("curl -fsSL {$host}/downloads/agent-install.sh | sudo bash -s -- \\\n  {$host} <enroll-token>") !!}
                <p>The installer downloads a static agent + kopia to <code>/opt/backup</code>, enrolls the host, and installs a <code>backup-agent</code> systemd service that polls for jobs. Check it with <code>systemctl status backup-agent</code>.</p>
            {!! $close('Per host you protect') !!}

            {!! $open('connectors', 'Backup Connectors', $sections[3][2]) !!}
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="font-semibold text-slate-900">Agent</p><p class="mt-1 text-sm text-slate-600">Backs up its own host's files or databases (mysqldump / pg_dump).</p></div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="font-semibold text-slate-900">SSH / Rsync</p><p class="mt-1 text-sm text-slate-600">A gateway pulls a remote host's files over SSH (key or password). Back up one path, or several paths in one snapshot.</p></div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="font-semibold text-slate-900">SFTP</p><p class="mt-1 text-sm text-slate-600">Pulled over SSH, same as rsync.</p></div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="font-semibold text-slate-900">FTP</p><p class="mt-1 text-sm text-slate-600">A gateway mirrors a remote FTP account — handy for shared hosting with FTP-only access.</p></div>
                    <div class="rounded-xl border border-brand-200 bg-brand-50/60 p-4 sm:col-span-2"><p class="font-semibold text-slate-900">Multi-FTP</p><p class="mt-1 text-sm text-slate-600">One host, many FTP logins — ideal for a WHM/reseller server where you only have FTP to each cPanel account. Each login is pulled into <strong>its own folder</strong> in a single snapshot within the repository.</p></div>
                </div>
                <p>For agentless hosts, set the host's connection type, address, and credentials; the gateway agent in that Director does the pulling. Gateway prerequisites: <code>rsync</code>, <code>wget</code> (FTP), and DB client tools where relevant.</p>
            {!! $close('Push & pull models') !!}

            {!! $open('repositories', 'Repositories', $sections[4][2]) !!}
                <p>A repository is where snapshots land. Supported backends:</p>
                <ul class="space-y-2 text-slate-600 list-disc list-inside">
                    <li><strong>Filesystem</strong> — a path on the Manager/gateway (e.g. the default <code>/var/backups/backupmgr</code>). Best for centralized, on-box storage.</li>
                    <li><strong>S3 / S3-compatible</strong> — Amazon S3, Backblaze B2, Wasabi, MinIO, or your own StorageMGR instance. Best for offsite copies.</li>
                </ul>
                <p>Repositories are encrypted by kopia with a per-repo password, and snapshots are deduplicated across every host that shares the repository.</p>
            {!! $close('Where snapshots live') !!}

            {!! $open('schedule', 'Scheduling &amp; Retention', $sections[5][2]) !!}
                <p>Assign a job a schedule (prebuilt templates like <em>Daily 2 AM</em>, or a custom cron) and a retention policy (keep N daily/weekly/monthly). kopia prunes and runs maintenance automatically within the window you set under <strong>Settings → Maintenance</strong>.</p>
            {!! $close('Automation') !!}

            {!! $open('updates', 'Updates', $sections[6][2]) !!}
                <p>The Manager checks for new signed releases as part of its license check. When one is available you'll see a badge on <strong>Settings → Updates</strong> and a banner across the app. Click <strong>Update Now</strong>, or enable <strong>Automatic Updates</strong> to apply new releases automatically, soon after they're published. Each update is checksum-verified and the previous build is archived before it is applied.</p>
            {!! $close('Signed & verified') !!}

            {!! $open('license', 'Licensing', $sections[7][2]) !!}
                <p>Enter your license key under <strong>Settings → License</strong>. The install validates it against the vendor (signature-verified) and re-checks periodically; if the check can't be reached it runs on a grace window and never locks you out of a restore.</p>
            {!! $close('Vendor-validated') !!}

            <p id="noresults" class="hidden rounded-xl border border-dashed border-slate-300 bg-white px-4 py-8 text-center text-slate-500">No sections match “<span id="noresults-q" class="font-medium text-slate-700"></span>”.</p>
        </div>

        <footer class="mt-12 border-t border-slate-200 pt-6 text-sm text-slate-400">{{ config('brand.name') }} · self-hosted backup · v{{ $ver }}</footer>
    </main>
</div>

<script>
    // Copy buttons on code blocks.
    document.querySelectorAll('.copy-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var code = btn.parentElement.querySelector('code');
            if (!code) return;
            navigator.clipboard.writeText(code.textContent).then(function () {
                var old = btn.textContent; btn.textContent = 'Copied!';
                setTimeout(function () { btn.textContent = old; }, 1400);
            });
        });
    });

    // Client-side search: filter sections + sidebar, with match highlighting.
    (function () {
        var input = document.getElementById('docsearch');
        var clear = document.getElementById('docsearch-clear');
        var sections = Array.prototype.slice.call(document.querySelectorAll('section[data-doc]'));
        var none = document.getElementById('noresults');
        var noneQ = document.getElementById('noresults-q');

        function clearMarks(el) {
            el.querySelectorAll('mark').forEach(function (m) {
                var t = document.createTextNode(m.textContent);
                m.parentNode.replaceChild(t, m);
                m.parentNode.normalize && m.parentNode.normalize();
            });
        }
        function highlight(el, q) {
            var walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT, null);
            var nodes = [], n;
            while ((n = walker.nextNode())) {
                if (n.parentElement.closest('pre')) continue; // don't touch code
                if (n.nodeValue.toLowerCase().indexOf(q) !== -1) nodes.push(n);
            }
            nodes.forEach(function (node) {
                var frag = document.createDocumentFragment(), text = node.nodeValue, low = text.toLowerCase(), i = 0, idx;
                while ((idx = low.indexOf(q, i)) !== -1) {
                    if (idx > i) frag.appendChild(document.createTextNode(text.slice(i, idx)));
                    var mk = document.createElement('mark'); mk.textContent = text.slice(idx, idx + q.length);
                    frag.appendChild(mk); i = idx + q.length;
                }
                if (i < text.length) frag.appendChild(document.createTextNode(text.slice(i)));
                node.parentNode.replaceChild(frag, node);
            });
        }
        function run() {
            var q = input.value.trim().toLowerCase();
            clear.classList.toggle('hidden', q === '');
            var shown = 0;
            sections.forEach(function (sec) {
                clearMarks(sec);
                var match = !q || sec.textContent.toLowerCase().indexOf(q) !== -1;
                sec.classList.toggle('hidden', !match);
                if (match) { shown++; if (q) highlight(sec, q); }
                var id = sec.id.replace('sec-', '');
                var nav = document.querySelector('[data-nav="' + id + '"]');
                if (nav) nav.classList.toggle('hidden', !match);
            });
            none.classList.toggle('hidden', shown !== 0);
            noneQ.textContent = input.value;
        }
        input.addEventListener('input', run);
        clear.addEventListener('click', function () { input.value = ''; run(); input.focus(); });
        document.addEventListener('keydown', function (e) {
            if (e.key === '/' && document.activeElement !== input) { e.preventDefault(); input.focus(); }
            if (e.key === 'Escape' && document.activeElement === input) { input.value = ''; run(); input.blur(); }
        });
    })();

    // Scrollspy: highlight the sidebar link for the section in view.
    (function () {
        var links = {};
        document.querySelectorAll('[data-nav]').forEach(function (a) { links[a.getAttribute('data-nav')] = a; });
        var heads = Object.keys(links).map(function (id) { return document.getElementById(id); }).filter(Boolean);
        function spy() {
            var top = null, y = 120;
            heads.forEach(function (h) { if (h.getBoundingClientRect().top - y <= 0) top = h.id; });
            Object.keys(links).forEach(function (id) { links[id].classList.toggle('active', id === top); });
        }
        document.addEventListener('scroll', spy, { passive: true });
        spy();
    })();
</script>
</body>
</html>
