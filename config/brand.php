<?php

// Branding. Rename the whole product from one place. These defaults can be
// overridden by env, and (once the Branding settings screen lands) by DB
// settings applied at boot — matching the DB-driven config pattern.
return [
    'name' => env('BRAND_NAME', env('APP_NAME', 'BackupMGR')),
    'tagline' => env('BRAND_TAGLINE', 'Self-Hosted Backup'),
    // Accent hex; overrides the cyan brand ramp at runtime. Settable in the UI.
    'accent' => env('BRAND_ACCENT', '#2563eb'),
    // Logo/favicon glyph (an x-icon name). Distinct per product.
    'icon' => env('BRAND_ICON', 'archive'),
];
