<?php

test('versioned API routes enforce the configured baseline rate limit', function () {
    config()->set('rate_limiting.api.per_minute', 2);

    $client = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.45']);

    $client->get('/api/v1')
        ->assertNoContent()
        ->assertHeader('X-RateLimit-Limit', '2')
        ->assertHeader('X-RateLimit-Remaining', '1');

    $client->get('/api/v1')
        ->assertNoContent()
        ->assertHeader('X-RateLimit-Remaining', '0');

    $client->getJson('/api/v1')
        ->assertTooManyRequests()
        ->assertHeader('Retry-After')
        ->assertHeader('X-RateLimit-Limit', '2')
        ->assertHeader('X-RateLimit-Remaining', '0')
        ->assertJson([
            'success' => false,
            'code' => 'RATE_LIMITED',
            'message' => 'Too many requests. Please try again later.',
        ]);
});

test('the API limiter uses the isolated testing cache store', function () {
    expect(config('cache.limiter'))->toBe('array');
});
