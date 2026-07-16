<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup — Activate License — {{ config('brand.name') }}</title>
    <x-tailwind-cdn />
    <x-accent-style />
</head>
<body class="h-full bg-slate-50">
<div class="min-h-full flex flex-col lg:flex-row">

    {{-- Brand panel --}}
    <div class="lg:w-1/2 bg-chrome text-white px-8 py-12 lg:p-16 flex flex-col justify-between">
        <x-brand class="text-white" />
        <div class="hidden lg:block max-w-md">
            <h2 class="text-3xl font-semibold tracking-tight">Almost There.</h2>
            <p class="mt-4 text-slate-300 leading-relaxed">
                Enter your {{ config('brand.name') }} license key to finish. Your key
                unlocks updates and support. {{ config('brand.name') }} never locks you
                out — you can always add or change your key later.
            </p>
            <ul class="mt-8 space-y-3 text-sm text-slate-300">
                <li class="flex items-center gap-2 opacity-60"><x-icon name="check-circle" class="w-5 h-5 text-brand-400" /> Admin Account Created</li>
                <li class="flex items-center gap-2"><x-icon name="key" class="w-5 h-5 text-brand-400" /> Activate Your License</li>
            </ul>
        </div>
        <p class="text-xs text-slate-400">{{ config('brand.name') }} &middot; {{ config('brand.tagline') }}</p>
    </div>

    {{-- Form panel --}}
    <div class="lg:w-1/2 flex items-center justify-center px-6 py-12">
        <div class="w-full max-w-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-brand-600">Step 2 of 2</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Activate License</h1>
            <p class="mt-1 text-sm text-slate-500">Enter the license key issued by ScriptGain.</p>

            @if ($errors->any())
                <div class="mt-6">
                    <x-alert type="danger">{{ $errors->first() }}</x-alert>
                </div>
            @endif

            @if (session('warning'))
                <div class="mt-6">
                    <x-alert type="warn">{{ session('warning') }}</x-alert>
                </div>
            @endif

            <form method="POST" action="{{ route('setup.license') }}" class="mt-6 space-y-5">
                @csrf
                <x-field label="License Key" for="key" hint="Looks like XXXX-XXXX-XXXX-XXXX.">
                    <x-input id="key" name="key" type="text" :value="old('key')" autofocus autocomplete="off" placeholder="VM88-KE46-Z72N-E4VK" />
                </x-field>

                <x-button type="submit" class="w-full">Activate &amp; Finish</x-button>
            </form>

            {{-- Secondary: finish without a key (product is lenient). --}}
            <form method="POST" action="{{ route('setup.license') }}" class="mt-4 text-center">
                @csrf
                <input type="hidden" name="action" value="skip">
                <button type="submit" class="text-sm text-slate-500 hover:text-slate-700 underline underline-offset-2">
                    I'll Add My License Later
                </button>
            </form>
        </div>
    </div>

</div>
</body>
</html>
