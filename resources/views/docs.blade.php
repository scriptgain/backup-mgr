<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50 scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Documentation — {{ config('brand.name') }}</title>
    <x-tailwind-cdn />
</head>
@php $host = rtrim(config('app.url'), '/'); @endphp
<body class="min-h-full text-slate-800">
<div class="mx-auto max-w-5xl px-4 py-10 lg:grid lg:grid-cols-[200px_1fr] lg:gap-10">
    <aside class="hidden lg:block">
        <div class="sticky top-10">
            <a href="{{ url('/') }}" class="text-sm font-semibold text-brand-700">← {{ config('brand.name') }}</a>
            <nav class="mt-4 flex flex-col gap-1 text-sm">
                @foreach (['overview' => 'Overview', 'master' => 'Install the Master', 'agents' => 'Enroll an Agent', 'connectors' => 'Backup Connectors', 'repositories' => 'Repositories', 'schedule' => 'Scheduling & Retention', 'updates' => 'Updates', 'license' => 'Licensing'] as $id => $label)
                    <a href="#{{ $id }}" class="rounded-lg px-3 py-1.5 text-slate-600 hover:bg-slate-100 hover:text-slate-900">{{ $label }}</a>
                @endforeach
            </nav>
        </div>
    </aside>

    <main class="min-w-0 space-y-10">
        <header>
            <h1 class="text-3xl font-bold text-slate-900">{{ config('brand.name') }} Documentation</h1>
            <p class="mt-2 text-slate-600">Self-hosted backup control plane: a manager you install once, and lightweight agents you drop onto each host you want to protect.</p>
        </header>

        @php
            $section = fn ($id, $title) => '<h2 id="'.$id.'" class="text-xl font-semibold text-slate-900 scroll-mt-10">'.$title.'</h2>';
            $code = fn ($c) => '<pre class="mt-3 overflow-x-auto rounded-xl bg-slate-900 px-4 py-3 text-sm text-slate-100"><code>'.$c.'</code></pre>';
        @endphp

        <section class="space-y-3">
            {!! $section('overview', 'Overview') !!}
            <p class="text-slate-600">The <strong>Manager</strong> queues jobs and stores backups; it never connects to your hosts. <strong>Agents</strong> poll the Manager over outbound HTTPS and do the work — either backing up their own host (<em>agent</em> connector) or acting as a <em>gateway</em> that pulls a remote host over SSH/FTP (<em>agentless</em> connectors). Snapshots are written with a bundled <a href="https://kopia.io" class="text-brand-700 hover:underline">kopia</a> into per-host repositories.</p>
            <p class="text-slate-600">Supported OS: Linux x86_64 (Ubuntu 22.04+, Debian 12+).</p>
        </section>

        <section class="space-y-3">
            {!! $section('master', 'Install the Manager') !!}
            <p class="text-slate-600">On a fresh Ubuntu 22.04+/Debian 12 server, clone the repo and run the installer. It provisions PHP, MariaDB, nginx, the app, a queue worker + scheduler, and (with <code>SSL=1</code>) a Let's Encrypt certificate.</p>
            {!! $code("git clone https://github.com/scriptgain/backup-mgr.git\ncd backup-mgr\nsudo DOMAIN=backup.example.com SSL=1 EMAIL=you@example.com \\\n  LICENSE_KEY=XXXX-XXXX-XXXX-XXXX ./deploy/install-master.sh") !!}
            <p class="text-slate-600">Point DNS at the server first so the certificate can be issued. After install, create your admin user and log in. A default <em>Local Director</em> and a <em>Local Backups</em> repository are provisioned automatically.</p>
        </section>

        <section class="space-y-3">
            {!! $section('agents', 'Enroll an Agent') !!}
            <p class="text-slate-600">In the Manager, add a Host (type <em>Agent</em>) to get a one-time enrollment token. Then, on the host you want to back up:</p>
            {!! $code("curl -fsSL {$host}/downloads/agent-install.sh | sudo bash -s -- \\\n  {$host} <enroll-token>") !!}
            <p class="text-slate-600">The installer downloads a static agent + kopia to <code>/opt/backup</code>, enrolls the host, and installs a <code>backup-agent</code> systemd service that polls for jobs. Check it with <code>systemctl status backup-agent</code>.</p>
        </section>

        <section class="space-y-3">
            {!! $section('connectors', 'Backup Connectors') !!}
            <ul class="space-y-2 text-slate-600 list-disc list-inside">
                <li><strong>Agent</strong> — the agent backs up its own host's files or databases (mysqldump / pg_dump).</li>
                <li><strong>SSH / Rsync</strong> — a gateway agent pulls a remote host's files over SSH (key or password). The gateway is any agent in the same Director.</li>
                <li><strong>SFTP</strong> — pulled over SSH, same as rsync.</li>
                <li><strong>FTP</strong> — a gateway mirrors a remote FTP account (handy for shared hosting with FTP-only access).</li>
            </ul>
            <p class="text-slate-600">For agentless hosts, set the host's connection type, address, and credentials; the gateway agent in that Director does the pulling. Gateway prerequisites: <code>rsync</code>, <code>wget</code> (FTP), and DB client tools where relevant.</p>
        </section>

        <section class="space-y-3">
            {!! $section('repositories', 'Repositories') !!}
            <p class="text-slate-600">A repository is where snapshots land. Supported backends:</p>
            <ul class="space-y-2 text-slate-600 list-disc list-inside">
                <li><strong>Filesystem</strong> — a path on the Manager/gateway (e.g. the default <code>/var/backups/backupmgr</code>). Best for centralized, on-box storage.</li>
                <li><strong>S3 / S3-compatible</strong> — Amazon S3, Backblaze B2, Wasabi, MinIO, or your own StorageMGR instance. Best for offsite copies.</li>
            </ul>
            <p class="text-slate-600">Repositories are encrypted by kopia with a per-repo password.</p>
        </section>

        <section class="space-y-3">
            {!! $section('schedule', 'Scheduling & Retention') !!}
            <p class="text-slate-600">Assign a job a schedule (prebuilt templates like <em>Daily 2 AM</em>, or a custom cron) and a retention policy (keep N daily/weekly/monthly). kopia prunes and runs maintenance automatically within the window you set under Settings → Maintenance.</p>
        </section>

        <section class="space-y-3">
            {!! $section('updates', 'Updates') !!}
            <p class="text-slate-600">The Manager checks for new signed releases as part of its license check. When one is available you'll see a badge on <strong>Settings → Updates</strong> and a banner across the app. Click <strong>Update Now</strong>, or enable <strong>Automatic Updates</strong> to apply new releases overnight. Each update is checksum-verified and the previous build is archived before it is applied.</p>
        </section>

        <section class="space-y-3">
            {!! $section('license', 'Licensing') !!}
            <p class="text-slate-600">Enter your license key under <strong>Settings → License</strong>. The install validates it against the vendor (signature-verified) and re-checks periodically; if the check can't be reached it runs on a grace window and never locks you out of a restore.</p>
        </section>

        <footer class="border-t border-slate-200 pt-6 text-sm text-slate-400">{{ config('brand.name') }} · self-hosted backup</footer>
    </main>
</div>
</body>
</html>
