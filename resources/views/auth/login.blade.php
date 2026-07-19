<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In — {{ config('brand.name') }}</title>
    <x-tailwind-cdn />
    <x-accent-style />
</head>
<body class="h-full bg-slate-50">
<div class="min-h-full flex flex-col lg:flex-row">

    {{-- Brand panel --}}
    <div class="lg:w-1/2 bg-chrome text-white px-8 py-12 lg:p-16 flex flex-col justify-between">
        <x-brand class="text-white" />
        <div class="hidden lg:block max-w-md">
            <h2 class="text-3xl font-semibold tracking-tight">Backups you can actually restore.</h2>
            <p class="mt-4 text-slate-300 leading-relaxed">
                One control panel for your whole fleet. Directors, hosts, schedules,
                retention, and restore points, all encrypted and deduplicated to your own storage.
            </p>
            <ul class="mt-8 space-y-3 text-sm text-slate-300">
                <li class="flex items-center gap-2"><x-icon name="check-circle" class="w-5 h-5 text-brand-400" /> Agent, SSH, SFTP, FTP &amp; rsync connectors</li>
                <li class="flex items-center gap-2"><x-icon name="check-circle" class="w-5 h-5 text-brand-400" /> Content-addressed dedupe + compression</li>
                <li class="flex items-center gap-2"><x-icon name="check-circle" class="w-5 h-5 text-brand-400" /> Browse and restore any point in time</li>
            </ul>
        </div>
        <p class="text-xs text-slate-400">{{ config('brand.name') }} &middot; {{ config('brand.tagline') }}</p>
    </div>

    {{-- Form panel --}}
    <div class="lg:w-1/2 flex items-center justify-center px-6 py-12">
        <div class="w-full max-w-sm">
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Sign In</h1>
            <p class="mt-1 text-sm text-slate-500">Welcome back. Enter your credentials to continue.</p>

            @if ($errors->any())
                <div class="mt-6">
                    <x-alert type="danger">{{ $errors->first() }}</x-alert>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
                @csrf
                <x-field label="Email" for="email" required>
                    <x-input id="email" name="email" type="email" :value="old('email')" required autofocus autocomplete="username" placeholder="you@example.com" />
                </x-field>
                <x-field label="Password" for="password" required>
                    <x-input id="password" name="password" type="password" required autocomplete="current-password" placeholder="••••••••" />
                </x-field>

                <x-toggle name="remember" label="Remember Me" />

                <x-button type="submit" class="w-full">Sign In</x-button>
            </form>
        </div>
    </div>

</div>
</body>
</html>
