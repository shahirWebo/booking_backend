<?php

test('the failed-job retention task is registered safely in UTC', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('queue:prune-failed')
        ->assertSuccessful();
});

test('scheduler configuration uses the isolated testing lock store', function () {
    expect(config('scheduler.lock_store'))->toBe('array')
        ->and(config('scheduler.failed_jobs_retention_hours'))->toBe(720)
        ->and(config('scheduler.failed_jobs_prune_time'))->toBe('03:17')
        ->and(config('scheduler.mutex_expiration_minutes'))->toBe(60);
});
