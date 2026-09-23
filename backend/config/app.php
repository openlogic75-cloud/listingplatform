<?php

return [

    'name' => env('APP_NAME', 'Shekuthi'),

    'env' => env('APP_ENV', 'production'),

    'debug' => (bool) env('APP_DEBUG', false),

    'require_email_verification' => filter_var(
        env('REQUIRE_EMAIL_VERIFICATION', true),
        FILTER_VALIDATE_BOOLEAN,
    ),

    'require_listing_approval' => filter_var(
        env('REQUIRE_LISTING_APPROVAL', true),
        FILTER_VALIDATE_BOOLEAN,
    ),

    'require_listing_image_consent' => filter_var(
        env('REQUIRE_LISTING_IMAGE_CONSENT', true),
        FILTER_VALIDATE_BOOLEAN,
    ),

    'url' => env('APP_URL', 'http://localhost'),

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    'currency_symbol' => env('CURRENCY_SYMBOL', '₹'),

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
