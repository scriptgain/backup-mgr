@props(['name', 'value' => '1', 'checked' => false, 'icon' => null])
{{-- A real toggle switch that submits natively (no JS). Wraps a visually-hidden
     checkbox; the visible track/knob is driven purely by :checked via CSS, so it
     works in multi-select groups (name="x[]") and survives the purge-free CDN
     build. Brand accent comes from the app's Tailwind --color-brand-600 token. --}}
@once
    <style>
        .vx-switch{display:inline-flex;align-items:center;gap:.6rem;cursor:pointer;user-select:none;font-size:.875rem;font-weight:500;color:#334155;}
        .vx-switch > input{position:absolute;width:1px;height:1px;opacity:0;margin:0;pointer-events:none;}
        .vx-switch-track{position:relative;display:inline-flex;flex:0 0 auto;width:2.75rem;height:1.5rem;border-radius:9999px;background:#cbd5e1;transition:background .15s;}
        .vx-switch-knob{position:absolute;top:.25rem;left:.25rem;width:1rem;height:1rem;border-radius:9999px;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.25);transition:transform .15s;}
        .vx-switch > input:checked ~ .vx-switch-track{background:var(--color-brand-600,#4f46e5);}
        .vx-switch > input:checked ~ .vx-switch-track .vx-switch-knob{transform:translateX(1.25rem);}
        .vx-switch > input:focus-visible ~ .vx-switch-track{box-shadow:0 0 0 2px rgba(99,102,241,.55);}
        .vx-switch:hover .vx-switch-track{filter:brightness(.97);}
        .vx-switch svg{width:1rem;height:1rem;flex:0 0 auto;color:#94a3b8;}
    </style>
@endonce
<label {{ $attributes->merge(['class' => 'vx-switch']) }}>
    <input type="checkbox" name="{{ $name }}" value="{{ $value }}" @checked($checked)>
    <span class="vx-switch-track"><span class="vx-switch-knob"></span></span>
    @if ($icon)<x-icon :name="$icon" />@endif
    <span>{{ $slot }}</span>
</label>
