<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Versioned API Baseline
    |--------------------------------------------------------------------------
    |
    | This is a broad abuse-control ceiling for every /api/v1 route. Endpoint
    | families with higher risk use narrower limiters in their owning module.
    |
    */

    'api' => [
        'per_minute' => (int) env('API_RATE_LIMIT_PER_MINUTE', 120),
    ],

];
