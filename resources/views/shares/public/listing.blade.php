@php
    $fmt = function ($b) {
        if ($b === null) return '';
        if ($b == 0) return '0 B';
        $u = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log(max($b, 1), 1024));
        return round($b / (1024 ** $i), 1) . ' ' . $u[$i];
    };
    $brand = config('brand.name', 'BackupMGR');
    $accent = config('brand.accent', '#06b6d4');
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $share->name }}</title>
    <style>
        :root { --accent: {{ $accent }}; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, sans-serif; background: #f8fafc; color: #0f172a; }
        .wrap { max-width: 820px; margin: 0 auto; padding: 32px 20px; }
        header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
        h1 { font-size: 20px; margin: 0; }
        .crumbs { font-size: 13px; color: #64748b; margin-bottom: 16px; }
        .crumbs a { color: var(--accent); text-decoration: none; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; }
        .row { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-top: 1px solid #f1f5f9; text-decoration: none; color: inherit; }
        .row:first-child { border-top: 0; }
        .row:hover { background: #f8fafc; }
        .name { flex: 1; font-size: 14px; word-break: break-all; }
        .size { font-size: 12px; color: #94a3b8; font-variant-numeric: tabular-nums; }
        .ico { width: 18px; height: 18px; color: #94a3b8; flex-shrink: 0; }
        .brand { font-size: 12px; color: #94a3b8; }
        .empty { padding: 40px; text-align: center; color: #94a3b8; font-size: 14px; }
        @media (prefers-color-scheme: dark) {
            body { background: #0b1120; color: #e2e8f0; }
            .card { background: #111827; border-color: #1f2937; }
            .row { border-color: #1f2937; }
            .row:hover { background: #0f172a; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <header>
            <h1>{{ $share->name }}</h1>
            <span class="brand">{{ $brand }}</span>
        </header>

        <div class="crumbs">
            <a href="{{ $urlBase }}">Home</a>
            @php $acc = ''; @endphp
            @foreach (($rel === '' ? [] : explode('/', $rel)) as $seg)
                @php $acc = trim($acc.'/'.$seg, '/'); @endphp
                / <a href="{{ $urlBase }}/{{ $acc }}">{{ $seg }}</a>
            @endforeach
        </div>

        <div class="card">
            @if ($parent !== null)
                <a class="row" href="{{ $urlBase }}{{ $parent === '' ? '' : '/'.$parent }}">
                    <svg class="ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3"/></svg>
                    <span class="name">..</span>
                </a>
            @endif
            @forelse ($entries as $e)
                <a class="row" href="{{ $urlBase }}/{{ $e->rel }}"@if (! $e->is_dir) target="_blank"@endif>
                    @if ($e->is_dir)
                        <svg class="ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z"/></svg>
                    @else
                        <svg class="ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M3.375 7.5h17.25"/></svg>
                    @endif
                    <span class="name">{{ $e->name }}</span>
                    <span class="size">{{ $fmt($e->size) }}</span>
                </a>
            @empty
                <div class="empty">This folder is empty.</div>
            @endforelse
        </div>
    </div>
</body>
</html>
