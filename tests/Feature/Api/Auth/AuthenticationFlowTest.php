<?php

use App\Domain\Auth\Repositories\OtpRequestRepository;
use App\Domain\Auth\Services\OtpCodeHasher;
use App\Jobs\SendOtpChallenge;
use App\Models\OtpRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\PersonalAccessToken;

test('the complete OTP authentication flow handles valid and invalid requests', function () {
    Queue::fake();

    $this->postJson(route('api.v1.auth.otp_requests.store'), [
        'mobile' => 'invalid-mobile',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'VALIDATION_ERROR');

    $requestResponse = $this->postJson(route('api.v1.auth.otp_requests.store'), [
        'mobile' => '98765 43210',
    ], [
        'X-Installation-ID' => '01JABCDEF0123456789ABCDE',
    ])
        ->assertAccepted()
        ->assertJsonPath('success', true);

    $challengeId = $requestResponse->json('data.otp_request_id');

    if (! is_string($challengeId)) {
        throw new RuntimeException('The OTP request response must contain a challenge ID.');
    }

    $otpRequests = app(OtpRequestRepository::class);
    $challenge = $otpRequests->find($challengeId);

    if (! $challenge instanceof OtpRequest) {
        throw new RuntimeException('The OTP challenge must be persisted.');
    }

    $code = '123456';
    $challenge->code_hash = app(OtpCodeHasher::class)->hash($challenge->id, $code);
    $otpRequests->save($challenge);

    Queue::assertPushed(
        SendOtpChallenge::class,
        fn (SendOtpChallenge $job): bool => $job->challengeId === $challengeId,
    );

    $this->postJson(route('api.v1.auth.otp_verifications.store'), [
        'otp_request_id' => $challengeId,
        'code' => '000000',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'VALIDATION_ERROR');

    $verifyResponse = $this->postJson(route('api.v1.auth.otp_verifications.store'), [
        'otp_request_id' => $challengeId,
        'code' => $code,
    ])
        ->assertOk()
        ->assertJsonPath('data.token_type', 'Bearer');

    $accessToken = $verifyResponse->json('data.access_token');

    if (! is_string($accessToken)) {
        throw new RuntimeException('The OTP verification response must contain an access token.');
    }

    expect(User::query()->sole()->mobile_number)->toBe('+919876543210')
        ->and(PersonalAccessToken::query()->count())->toBe(1);

    Auth::forgetGuards();

    $this->getJson(route('api.v1.auth.user.show'))
        ->assertUnauthorized();

    Auth::forgetGuards();

    $this->deleteJson(route('api.v1.auth.session.destroy'))
        ->assertUnauthorized();

    Auth::forgetGuards();

    $this->withToken($accessToken)
        ->getJson(route('api.v1.auth.user.show'))
        ->assertOk()
        ->assertJsonPath('data.mobile_number', '+919876543210');

    Auth::forgetGuards();

    $this->withToken($accessToken)
        ->deleteJson(route('api.v1.auth.session.destroy'))
        ->assertNoContent();

    expect(PersonalAccessToken::query()->count())->toBe(0);

    Auth::forgetGuards();

    $this->withToken($accessToken)
        ->getJson(route('api.v1.auth.user.show'))
        ->assertUnauthorized();
});
