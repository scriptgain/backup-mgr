@php $accent = config('brand.accent'); @endphp
@if ($accent && strtolower($accent) !== '#06b6d4')
    {{-- Re-tint the entire brand ramp from the chosen accent (custom brands only;
         the default cyan uses the hand-tuned scale in app.css). --}}
    <style>
        :root {
            --accent: {{ $accent }};
            --color-brand-50: color-mix(in srgb, var(--accent), white 92%);
            --color-brand-100: color-mix(in srgb, var(--accent), white 85%);
            --color-brand-200: color-mix(in srgb, var(--accent), white 72%);
            --color-brand-300: color-mix(in srgb, var(--accent), white 55%);
            --color-brand-400: color-mix(in srgb, var(--accent), white 30%);
            --color-brand-500: var(--accent);
            --color-brand-600: color-mix(in srgb, var(--accent), black 12%);
            --color-brand-700: color-mix(in srgb, var(--accent), black 25%);
            --color-brand-800: color-mix(in srgb, var(--accent), black 40%);
            --color-brand-900: color-mix(in srgb, var(--accent), black 52%);
            --color-brand-950: color-mix(in srgb, var(--accent), black 68%);
        }
    </style>
@endif
