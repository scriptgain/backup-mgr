@php $brand = config('brand.name', 'BackupMGR'); $accent = config('brand.accent', '#06b6d4'); @endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $share->name }}</title>
    <style>
        :root { --accent: {{ $accent }}; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, sans-serif; background: #f8fafc; color: #0f172a; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 32px; width: 360px; max-width: 92vw; box-shadow: 0 10px 30px rgba(2,6,23,.06); }
        h1 { font-size: 18px; margin: 0 0 4px; }
        p { font-size: 13px; color: #64748b; margin: 0 0 20px; }
        label { font-size: 13px; font-weight: 600; display: block; margin-bottom: 6px; }
        input { width: 100%; padding: 10px 12px; font-size: 14px; border: 1px solid #cbd5e1; border-radius: 10px; margin-bottom: 14px; }
        input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 25%, transparent); }
        button { width: 100%; padding: 10px; font-size: 14px; font-weight: 600; color: #fff; background: var(--accent); border: 0; border-radius: 10px; cursor: pointer; }
        .err { color: #e11d48; font-size: 13px; margin-bottom: 12px; }
        .brand { text-align: center; font-size: 12px; color: #94a3b8; margin-top: 16px; }
        @media (prefers-color-scheme: dark) { body { background: #0b1120; color: #e2e8f0; } .card { background: #111827; border-color: #1f2937; } input { background: #0b1120; border-color: #334155; color: #e2e8f0; } }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ $share->name }}</h1>
        <p>This share is password protected.</p>
        @if ($errors->any())<div class="err">{{ $errors->first('password') }}</div>@endif
        <form method="POST" action="{{ route('share.unlock', ['token' => $share->token]) }}">
            @csrf
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autofocus autocomplete="off">
            <button type="submit">Unlock</button>
        </form>
        <div class="brand">{{ $brand }}</div>
    </div>
</body>
</html>
