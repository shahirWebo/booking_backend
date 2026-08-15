<?php

test('the test process uses isolated deterministic framework drivers', function () {
    expect(config('app.env'))->toBe('testing')
        ->and(config('app.debug'))->toBeFalse()
        ->and(config('app.url'))->toBe('http://localhost')
        ->and(config('app.key'))->toBe('base64:MDEyMzQ1Njc4OTAxMjM0NTY3ODkwMTIzNDU2Nzg5MDE=')
        ->and(config('database.default'))->toBe('sqlite')
        ->and(config('database.connections.sqlite.database'))->toBe(':memory:')
        ->and(config('cache.default'))->toBe('array')
        ->and(config('cache.limiter'))->toBe('array')
        ->and(config('scheduler.lock_store'))->toBe('array')
        ->and(config('queue.default'))->toBe('sync')
        ->and(config('queue.failed.driver'))->toBeNull()
        ->and(config('session.driver'))->toBe('array')
        ->and(config('mail.default'))->toBe('array')
        ->and(config('broadcasting.default'))->toBeNull()
        ->and(config('logging.default'))->toBeNull();
});

test('the test cache namespace cannot collide with deployed cache entries', function () {
    expect(config('cache.prefix'))->toStartWith('turf_booking:testing:cache:');
});
