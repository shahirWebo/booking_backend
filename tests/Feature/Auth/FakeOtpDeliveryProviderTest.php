<?php

use App\Domain\Auth\Contracts\OtpDeliveryProvider;
use App\Domain\Auth\Data\OtpDeliveryOutcome;
use App\Domain\Auth\Data\OtpDeliveryRequest;
use App\Domain\Auth\Providers\FakeOtpDeliveryProvider;
use Carbon\CarbonImmutable;

test('the local and test OTP provider binding is the deterministic fake', function () {
    $provider = app(OtpDeliveryProvider::class);

    expect($provider)->toBeInstanceOf(FakeOtpDeliveryProvider::class)
        ->and(app(OtpDeliveryProvider::class))->toBe($provider);
});

test('the fake records only safe delivery details and exposes the code to tests', function () {
    $provider = app(FakeOtpDeliveryProvider::class);
    $request = new OtpDeliveryRequest(
        '01JABCDEF0123456789ABCDE',
        '+919876543210',
        '012345',
        CarbonImmutable::now('UTC')->addMinutes(5),
        'en',
        'default',
        'opaque-effect-key',
    );

    $result = $provider->send($request);
    $delivery = $provider->deliveries()[$request->challengeId];

    expect($result->outcome)->toBe(OtpDeliveryOutcome::Accepted)
        ->and($result->providerReference)->toBe('fake:'.$request->challengeId)
        ->and($delivery->challengeId)->toBe($request->challengeId)
        ->and($delivery->outcome)->toBe(OtpDeliveryOutcome::Accepted)
        ->and(array_keys(get_object_vars($delivery)))->toBe([
            'challengeId',
            'outcome',
            'providerReference',
        ])
        ->and($provider->testCodeFor($request->challengeId))->toBe('012345');
});

test('the fake returns the configured controlled outcome', function (OtpDeliveryOutcome $outcome) {
    config()->set('otp.fake_delivery_outcome', $outcome->value);
    $provider = app(FakeOtpDeliveryProvider::class);
    $request = new OtpDeliveryRequest(
        '01JABCDEF0123456789ABCDE',
        '+919876543210',
        '012345',
        CarbonImmutable::now('UTC')->addMinutes(5),
        'en',
        'default',
        'opaque-effect-key',
    );

    $result = $provider->send($request);

    expect($result->outcome)->toBe($outcome)
        ->and($result->retryAfterSeconds)->toBe(
            $outcome === OtpDeliveryOutcome::TransientFailure ? 5 : null,
        );
})->with(OtpDeliveryOutcome::cases());
