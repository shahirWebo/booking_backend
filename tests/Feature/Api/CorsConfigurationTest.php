<?php

test('an allowed browser origin receives CORS response headers for the versioned API', function () {
    $this->get('/api/v1', ['Origin' => 'http://spa.test'])
        ->assertNoContent()
        ->assertHeader('Access-Control-Allow-Origin', 'http://spa.test')
        ->assertHeader('Access-Control-Expose-Headers', 'ETag, Idempotency-Replayed, Location, Retry-After, X-RateLimit-Limit, X-RateLimit-Remaining, X-Request-ID')
        ->assertHeader('Vary', 'Origin');
});

test('an allowed API preflight advertises only the documented browser contract', function () {
    $this->call('OPTIONS', '/api/v1', [], [], [], [
        'HTTP_ORIGIN' => 'http://spa.test',
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'Authorization, Content-Type, Idempotency-Key, X-Request-ID',
    ])
        ->assertNoContent()
        ->assertHeader('Access-Control-Allow-Origin', 'http://spa.test')
        ->assertHeader('Access-Control-Allow-Methods', 'GET, HEAD, POST, PUT, PATCH, DELETE, OPTIONS')
        ->assertHeader('Access-Control-Allow-Headers', 'accept, authorization, content-type, idempotency-key, x-request-id')
        ->assertHeader('Access-Control-Max-Age', '600')
        ->assertHeaderMissing('Access-Control-Allow-Credentials');
});

test('an unapproved origin is not granted API CORS access', function () {
    $this->call('OPTIONS', '/api/v1', [], [], [], [
        'HTTP_ORIGIN' => 'https://untrusted.example',
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
    ])
        ->assertNoContent()
        ->assertHeaderMissing('Access-Control-Allow-Origin');
});

test('CORS configuration does not use wildcard origins or browser credentials', function () {
    expect(config('cors.paths'))->toBe(['api/*'])
        ->and(config('cors.allowed_origins'))->toBe(['http://spa.test', 'http://other.test'])
        ->and(config('cors.allowed_origins_patterns'))->toBe([])
        ->and(config('cors.supports_credentials'))->toBeFalse();
});
