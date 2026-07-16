<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Two-Factor — {{ config('brand.name') }}</title>
    <x-tailwind-cdn />
    <x-accent-style />
</head>
<body class="h-full bg-slate-50">
<div class="min-h-full flex items-center justify-center px-6 py-12">
    <div class="w-full max-w-sm">
        <div class="flex justify-center mb-6"><x-brand /></div>
        <x-card>
            <div class="text-center">
                <span class="mx-auto inline-flex items-center justify-center w-12 h-12 rounded-xl bg-brand-50 text-brand-600 ring-1 ring-brand-100"><x-icon name="shield" class="w-6 h-6" /></span>
                <h1 class="mt-4 text-lg font-semibold text-slate-900">Two-Factor Verification</h1>
                <p class="mt-1 text-sm text-slate-500">Enter the 6-digit code from your authenticator app.</p>
            </div>
            @if ($errors->any())
                <div class="mt-4"><x-alert type="danger">{{ $errors->first() }}</x-alert></div>
            @endif
            <form method="POST" action="{{ route('2fa.challenge') }}" class="mt-6 space-y-4">
                @csrf
                <x-input name="code" inputmode="numeric" autocomplete="one-time-code" placeholder="123456" required autofocus data-lpignore="true" class="text-center text-lg tracking-widest" />
                <x-button type="submit" class="w-full">Verify</x-button>
            </form>
        </x-card>
        <p class="mt-4 text-center text-xs text-slate-400"><a href="{{ route('login') }}" class="hover:text-slate-600">Back to sign in</a></p>
    </div>
</div>
</body>
</html>
