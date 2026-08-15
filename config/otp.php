<?php

return [
    'default_region' => env('OTP_DEFAULT_REGION', 'IN'),

    'permitted_regions' => array_values(array_filter(array_map(
        trim(...),
        explode(',', (string) env('OTP_PERMITTED_REGIONS', 'IN')),
    ))),

    // This is deliberately unset by default. Deployment secret management supplies it.
    'hash_pepper' => env('OTP_HASH_PEPPER'),

    // Independent HMAC key for private mobile/risk limiter lookup keys.
    'lookup_hmac_key' => env('OTP_LOOKUP_HMAC_KEY'),

    // A deterministic adapter is permitted only for local and isolated tests.
    'delivery_provider' => env('OTP_DELIVERY_PROVIDER', 'fake'),

    'fake_delivery_outcome' => env('OTP_FAKE_DELIVERY_OUTCOME', 'accepted'),

    'code_lifetime_seconds' => (int) env('OTP_CODE_LIFETIME_SECONDS', 300),
    'resend_cooldown_seconds' => (int) env('OTP_RESEND_COOLDOWN_SECONDS', 60),
    'max_verification_attempts' => (int) env('OTP_MAX_VERIFICATION_ATTEMPTS', 5),

    'rate_limits' => [
        'mobile_15_minutes' => ['max_attempts' => 3, 'decay_seconds' => 900],
        'mobile_hour' => ['max_attempts' => 5, 'decay_seconds' => 3600],
        'mobile_day' => ['max_attempts' => 10, 'decay_seconds' => 86400],
        'ip_15_minutes' => ['max_attempts' => 10, 'decay_seconds' => 900],
        'ip_hour' => ['max_attempts' => 30, 'decay_seconds' => 3600],
        'installation_15_minutes' => ['max_attempts' => 10, 'decay_seconds' => 900],
    ],
];
