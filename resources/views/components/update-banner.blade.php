@php
    $u = class_exists(\App\Services\UpdateService::class) ? \App\Services\UpdateService::status() : ['available' => false];
@endphp
@if (($u['available'] ?? false) && \Illuminate\Support\Facades\Route::has('settings.updates.show') && auth()->check() && auth()->user()->isAdmin())
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-amber-50 px-4 py-3 ring-1 ring-amber-200">
        <p class="text-sm text-amber-800">
            <span class="font-semibold">Update available.</span>
            Version {{ $u['latest'] }} is ready to install (you’re on {{ $u['current'] }}).
        </p>
        <a href="{{ route('settings.updates.show') }}" class="shrink-0 rounded-lg bg-amber-500 px-3 py-1.5 text-sm font-semibold text-white hover:bg-amber-600">Update Now</a>
    </div>
@endif
