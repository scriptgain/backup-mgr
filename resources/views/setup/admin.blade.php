<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup — Create Admin — {{ config('brand.name') }}</title>
    <x-tailwind-cdn />
    <x-accent-style />
</head>
<body class="h-full bg-slate-50">
<div class="min-h-full flex flex-col lg:flex-row">

    {{-- Brand panel --}}
    <div class="lg:w-1/2 bg-chrome text-white px-8 py-12 lg:p-16 flex flex-col justify-between">
        <x-brand class="text-white" />
        <div class="hidden lg:block max-w-md">
            <h2 class="text-3xl font-semibold tracking-tight">Welcome to {{ config('brand.name') }}.</h2>
            <p class="mt-4 text-slate-300 leading-relaxed">
                Let's get your install ready. First, create the administrator account
                that owns this control panel. Then activate your license and you're done.
            </p>
            <ul class="mt-8 space-y-3 text-sm text-slate-300">
                <li class="flex items-center gap-2"><x-icon name="check-circle" class="w-5 h-5 text-brand-400" /> Create Your Admin Account</li>
                <li class="flex items-center gap-2 opacity-60"><x-icon name="key" class="w-5 h-5 text-brand-400" /> Activate Your License</li>
            </ul>
        </div>
        <p class="text-xs text-slate-400">{{ config('brand.name') }} &middot; {{ config('brand.tagline') }}</p>
    </div>

    {{-- Form panel --}}
    <div class="lg:w-1/2 flex items-center justify-center px-6 py-12">
        <div class="w-full max-w-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-brand-600">Step 1 of 2</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Create Admin Account</h1>
            <p class="mt-1 text-sm text-slate-500">This account has full control of {{ config('brand.name') }}.</p>

            @if ($errors->any())
                <div class="mt-6">
                    <x-alert type="danger">{{ $errors->first() }}</x-alert>
                </div>
            @endif

            <form method="POST" action="{{ route('setup.admin') }}" class="mt-6 space-y-5">
                @csrf
                <x-field label="Full Name" for="name" required>
                    <x-input id="name" name="name" type="text" :value="old('name')" required autofocus autocomplete="name" placeholder="Jane Admin" />
                </x-field>
                <x-field label="Email" for="email" required>
                    <x-input id="email" name="email" type="email" :value="old('email')" required autocomplete="username" placeholder="you@example.com" />
                </x-field>
                <x-field label="Password" for="password" required>
                    <x-input id="password" name="password" type="password" required autocomplete="new-password" placeholder="At Least 8 Characters" />
                </x-field>
                <x-field label="Confirm Password" for="password_confirmation" required>
                    <x-input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" placeholder="Re-Enter Password" />
                </x-field>

                <x-button type="submit" class="w-full">Create Account &amp; Continue</x-button>
            </form>
        </div>
    </div>

</div>
</body>
</html>
