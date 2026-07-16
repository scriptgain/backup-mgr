@props(['label' => null, 'for' => null, 'hint' => null, 'error' => null, 'required' => false])
<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    @if ($label)
        <label @if ($for) for="{{ $for }}" @endif class="block text-sm font-medium text-slate-700">
            {{ $label }}
            @if ($required)<span class="text-rose-500">*</span>@endif
        </label>
    @endif
    {{ $slot }}
    @if ($error)
        <p class="text-sm text-rose-600">{{ $error }}</p>
    @elseif ($hint)
        <p class="text-sm text-slate-500">{{ $hint }}</p>
    @endif
</div>
