<?php

/**
 * Branding (M0.7). One file owns the product name, tagline and logo paths so a
 * brand change is a single edit here - never a sweep through views.
 *
 * Logo/icon paths are relative to backend/public/. Drop the real files in
 * per assets/brand/README.md; until then the views fall back to the wordmark.
 */
return [
    'name' => env('BRAND_NAME', 'Local Goods Marketplace'),

    'tagline' => env(
        'BRAND_TAGLINE',
        'Local goods, honest sourcing, direct connections.',
    ),

    // Rendered in the site header when the file exists in public/img/.
    'logo' => env('BRAND_LOGO', 'img/logo.svg'),
    'logo_png' => env('BRAND_LOGO_PNG', 'img/logo.png'),
    'favicon' => env('BRAND_FAVICON', 'img/favicon.png'),

    // Alt text. Meaningful imagery gets a description, per the accessibility floor.
    'logo_alt' => env('BRAND_LOGO_ALT', 'Local Goods Marketplace'),
];