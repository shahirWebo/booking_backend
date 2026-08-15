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

test('the fake OTP delivery provider is restricted to local and testing', function (string $environment) {
    EnvironmentConfiguration::assertOtpDeliveryProvider($environment, 'fake');

    expect(true)->toBeTrue();
})->with(['local', 'testing']);

test('the fake OTP delivery provider is rejected in deployed environments', function (string $environment) {
    expect(fn () => EnvironmentConfiguration::assertOtpDeliveryProvider($environment, 'fake'))
        ->toThrow(LogicException::class, "OTP_DELIVERY_PROVIDER=fake is not permitted in the {$environment} environment.");
})->with(['staging', 'production']);

test('an unsupported OTP delivery provider is rejected', function () {
    expect(fn () => EnvironmentConfiguration::assertOtpDeliveryProvider('local', 'live'))
        ->toThrow(LogicException::class, 'OTP_DELIVERY_PROVIDER must be set to a supported provider.');
});
