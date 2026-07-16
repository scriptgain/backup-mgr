@props(['icon' => 'folder', 'title' => 'Nothing Here Yet', 'description' => null])
<div {{ $attributes->merge(['class' => 'text-center py-12 px-6']) }}>
    <span class="mx-auto inline-flex items-center justify-center w-12 h-12 rounded-xl bg-slate-100 text-slate-400 ring-1 ring-slate-200">
        <x-icon :name="$icon" class="w-6 h-6" />
    </span>
    <h3 class="mt-4 text-sm font-semibold text-slate-900">{{ $title }}</h3>
    @if ($description)<p class="mt-1 text-sm text-slate-500 max-w-sm mx-auto">{{ $description }}</p>@endif
    @isset($action)<div class="mt-5 flex justify-center">{{ $action }}</div>@endisset
</div>
