<?php

return [

    // Third-party service credentials live here. Nothing secret is committed;
    // values come from .env only.

    'pii' => [
        // Key for blind-index HMAC hashes that make encrypted PII searchable.
        // Generate with: php -r "echo bin2hex(random_bytes(32));"
        'index_key' => env('PII_INDEX_KEY', ''),
    ],

    'fcm' => [
        'server_key' => env('FCM_SERVER_KEY', ''),
    ],

];
