<?php

test('the Redis cache store separates application data and lock connections', function () {
    expect(config('cache.stores.redis.connection'))->toBe('cache');
    expect(config('cache.stores.redis.lock_connection'))->toBe('cache_locks');
    expect(config('database.redis.cache.database'))->toBe('1');
    expect(config('database.redis.cache_locks.database'))->toBe('2');
    expect(config('cache.serializable_classes'))->toBeFalse();
});

test('the test environment uses the isolated array cache store', function () {
    expect(config('cache.default'))->toBe('array');
});
