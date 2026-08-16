<?php

use App\Domain\Auth\Actions\IssueOtpChallengeAction;
use App\Domain\Auth\Contracts\OtpDeliveryProvider;
use App\Domain\Auth\Enums\OtpRequestPurpose;
use App\Domain\Auth\Enums\OtpRequestStatus;
use App\Domain\Auth\Providers\FakeOtpDeliveryProvider;
use App\Domain\Auth\Repositories\OtpRequestRepository;
use App\Jobs\SendOtpChallenge;

test('the OTP delivery job sends only the active challenge and records provider acceptance', function () {
    $issued = app(IssueOtpChallengeAction::class)->execute('+919876543210', OtpRequestPurpose::Authentication);

    $provider = app(OtpDeliveryProvider::class);

    expect($provider)->toBeInstanceOf(FakeOtpDeliveryProvider::class);

    (new SendOtpChallenge($issued['challenge']->id, $issued['code']))->handle(
        $provider,
        app(OtpRequestRepository::class),
    );

    $challenge = $issued['challenge']->fresh();

    expect($challenge->status)->toBe(OtpRequestStatus::ProviderAccepted)
        ->and($challenge->delivery_provider_key)->toBe('fake')
        ->and($challenge->delivery_provider_reference)->toBe('fake:'.$challenge->id)
        ->and($provider->testCodeFor($challenge->id))->toBe($issued['code']);
});
