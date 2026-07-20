{{-- ORDERING HAZARD: this component loads the Alpine CDN. Any JS file that
     calls Alpine.data(), Alpine.store(), Alpine.directive() or Alpine.magic()
     MUST be loaded BEFORE this component, not after it. Deferred scripts run in
     document order and Alpine fires alpine:init the moment it starts, so a file
     loaded later attaches its listener after the event already fired and
     everything it registers silently never exists. Inline x-data keeps working
     either way, which is what makes this so easy to miss. --}}
{{-- Tailwind v4 + Alpine, served from CDN — no Vite build step.
     The design tokens (@theme / @layer base / @layer components) come straight
     from resources/css/app.css, inlined at runtime minus the build-only
     @import/@source lines the in-browser compiler does not use. Reading the
     file (rather than pasting the CSS here) keeps @theme/@apply out of the
     Blade source, so Blade never mistakes them for directives. --}}
@php
    $tokens = @file_get_contents(resource_path('css/app.css')) ?: '';
    // Drop @import "tailwindcss"; and @source globs — the browser build supplies
    // Tailwind itself and scans the live DOM for classes instead of files.
    $tokens = preg_replace('/^[ \t]*@(?:import|source)\b[^;]*;[ \t]*\R?/m', '', $tokens);

    // Bake the per-brand accent straight into the compiled theme so the browser
    // build emits the brand color itself. Doing it here (rather than only in the
    // separate x-accent-style block) avoids a cascade race against the CSS the
    // browser build injects at runtime. Mirrors x-accent-style's ramp formula.
    $accent = config('brand.accent');
    if ($accent && strtolower($accent) !== '#06b6d4') {
        $a = preg_replace('/[^#0-9a-zA-Z(),.% ]/', '', $accent); // keep it a plain color value
        $tokens .= "\n@theme {\n"
            ."  --color-brand-50: color-mix(in srgb, {$a}, white 92%);\n"
            ."  --color-brand-100: color-mix(in srgb, {$a}, white 85%);\n"
            ."  --color-brand-200: color-mix(in srgb, {$a}, white 72%);\n"
            ."  --color-brand-300: color-mix(in srgb, {$a}, white 55%);\n"
            ."  --color-brand-400: color-mix(in srgb, {$a}, white 30%);\n"
            ."  --color-brand-500: {$a};\n"
            ."  --color-brand-600: color-mix(in srgb, {$a}, black 12%);\n"
            ."  --color-brand-700: color-mix(in srgb, {$a}, black 25%);\n"
            ."  --color-brand-800: color-mix(in srgb, {$a}, black 40%);\n"
            ."  --color-brand-900: color-mix(in srgb, {$a}, black 52%);\n"
            ."  --color-brand-950: color-mix(in srgb, {$a}, black 68%);\n"
            ."}\n";
    }
@endphp
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<style type="text/tailwindcss">{!! $tokens !!}</style>
{{-- Alpine powers dropdowns, toggles, modals. The focus plugin must load before core. --}}
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/focus@3/dist/cdn.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
