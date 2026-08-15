<?php

use App\Support\EnvironmentConfiguration;

test('debug mode is permitted for local and testing environments', function (string $environment) {
    EnvironmentConfiguration::assertSafe($environment, true);

    expect(true)->toBeTrue();
})->with(['local', 'testing']);

test('debug mode is rejected for deployed environments', function (string $environment) {
    expect(fn () => EnvironmentConfiguration::assertSafe($environment, true))
        ->toThrow(LogicException::class, "APP_DEBUG must be false in the {$environment} environment.");
})->with(['staging', 'production']);

test('deployed environments permit debug mode when it is disabled', function (string $environment) {
    EnvironmentConfiguration::assertSafe($environment, false);

    expect(true)->toBeTrue();
})->with(['staging', 'production']);
