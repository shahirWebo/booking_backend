<?php

use Illuminate\Http\Request;

$configuredProxies = env('TRUSTED_PROXIES', '');

$proxies = is_string($configuredProxies)
    ? array_values(array_filter(
        array_map('trim', explode(',', $configuredProxies)),
        fn (string $proxy): bool => $proxy !== '' && $proxy !== '*' && $proxy !== '**',
    ))
    : [];

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Reverse Proxies
    |--------------------------------------------------------------------------
    |
    | Only deployment-managed proxy IPs or CIDRs belong here. Empty is safe:
    | client supplied forwarding headers are then ignored completely.
    |
    */

    'proxies' => $proxies,

    /*
    |--------------------------------------------------------------------------
    | Forwarded Headers
    |--------------------------------------------------------------------------
    |
    | Trust only the client-address and transport headers from those proxies.
    | Host and prefix headers remain untrusted to prevent URL/host poisoning.
    |
    */

    'headers' => Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_PORT,

];
