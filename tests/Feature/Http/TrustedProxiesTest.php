<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('api/v1/testing/proxy', function (Request $request) {
        return response()->json([
            'client_ip' => $request->ip(),
            'host' => $request->getHost(),
            'port' => $request->getPort(),
            'scheme' => $request->getScheme(),
        ]);
    });
});

test('forwarded client and transport headers are honored only from a trusted proxy', function () {
    $this->withServerVariables([
        'HTTP_HOST' => 'api.internal.test',
        'REMOTE_ADDR' => '10.0.0.10',
        'HTTP_X_FORWARDED_FOR' => '198.51.100.24, 10.0.0.10',
        'HTTP_X_FORWARDED_HOST' => 'attacker.example',
        'HTTP_X_FORWARDED_PORT' => '443',
        'HTTP_X_FORWARDED_PROTO' => 'https',
    ])->getJson('/api/v1/testing/proxy')
        ->assertOk()
        ->assertJson([
            'client_ip' => '198.51.100.24',
            'host' => 'localhost',
            'port' => 443,
            'scheme' => 'https',
        ]);
});

test('forwarded headers from an untrusted address are ignored', function () {
    $this->withServerVariables([
        'HTTP_HOST' => 'api.internal.test',
        'REMOTE_ADDR' => '203.0.113.10',
        'HTTP_X_FORWARDED_FOR' => '198.51.100.24',
        'HTTP_X_FORWARDED_HOST' => 'attacker.example',
        'HTTP_X_FORWARDED_PORT' => '443',
        'HTTP_X_FORWARDED_PROTO' => 'https',
    ])->getJson('/api/v1/testing/proxy')
        ->assertOk()
        ->assertJson([
            'client_ip' => '203.0.113.10',
            'host' => 'localhost',
            'port' => 8000,
            'scheme' => 'http',
        ]);
});

test('trusted proxy configuration has no catch-all proxy or host forwarding', function () {
    expect(config('trustedproxy.proxies'))->toBe(['10.0.0.10'])
        ->and(config('trustedproxy.headers') & Request::HEADER_X_FORWARDED_FOR)->not->toBe(0)
        ->and(config('trustedproxy.headers') & Request::HEADER_X_FORWARDED_PROTO)->not->toBe(0)
        ->and(config('trustedproxy.headers') & Request::HEADER_X_FORWARDED_PORT)->not->toBe(0)
        ->and(config('trustedproxy.headers') & Request::HEADER_X_FORWARDED_HOST)->toBe(0)
        ->and(config('trustedproxy.headers') & Request::HEADER_X_FORWARDED_PREFIX)->toBe(0);
});
