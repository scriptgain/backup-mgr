@props(['href' => null])
{{-- Wordmark. No chip/box behind the logo (house style). Inherits text color
     from the bar it sits in; the mark stays brand cyan. --}}
<a href="{{ $href ?? url('/') }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 font-semibold tracking-tight']) }}>
    <x-icon name="{{ config('brand.icon', 'shield') }}" class="w-6 h-6 text-brand-400 shrink-0" />
    <span class="text-lg">{{ config('brand.name') }}</span>
</a>
