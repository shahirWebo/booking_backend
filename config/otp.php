<?php

return [
    'default_region' => env('OTP_DEFAULT_REGION', 'IN'),

    'permitted_regions' => array_values(array_filter(array_map(
        trim(...),
        explode(',', (string) env('OTP_PERMITTED_REGIONS', 'IN')),
    ))),

    // This is deliberately unset by default. Deployment secret management supplies it.
    'hash_pepper' => env('OTP_HASH_PEPPER'),
];
