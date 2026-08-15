<?php

$configuredOrigins = env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173,http://127.0.0.1:5173');

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Browser access is limited to the versioned API. Native Flutter clients do
    | not require CORS; browser clients use bearer tokens rather than cookies.
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', is_string($configuredOrigins) ? $configuredOrigins : ''),
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'Idempotency-Key',
        'X-Request-ID',
    ],

    'exposed_headers' => [
        'ETag',
        'Idempotency-Replayed',
        'Location',
        'Retry-After',
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-Request-ID',
    ],

    'max_age' => (int) env('CORS_MAX_AGE', 600),

    'supports_credentials' => false,

];
