<?php

use App\Domain\Auth\Enums\OtpRequestStatus;
use App\Jobs\SendOtpChallenge;
use App\Models\OtpRequest;
use Illuminate\Support\Facades\Queue;

test('the OTP request endpoint accepts a challenge and queues encrypted delivery', function () {
    Queue::fake();

    $response = $this->postJson(route('api.v1.auth.otp_requests.store'), [
        'mobile' => '+919876543210',
    ]);

    $response
        ->assertAccepted()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.otp_request_id', fn (string $id): bool => str($id)->isUlid())
        ->assertJsonStructure(['data' => ['otp_request_id', 'expires_at', 'resend_available_at']])
        ->assertJsonMissingPath('data.mobile')
        ->assertJsonMissingPath('data.code');

    $challenge = OtpRequest::query()->sole();

    expect($challenge->status)->toBe(OtpRequestStatus::PendingDelivery)
        ->and($challenge->mobile_number_ciphertext)->toBe('+919876543210');

    Queue::assertPushed(SendOtpChallenge::class, fn (SendOtpChallenge $job): bool => $job->challengeId === $challenge->id && $job->queue === 'auth');
});

test('the OTP request endpoint normalizes a supported mobile number', function () {
    Queue::fake();

    $this->postJson(route('api.v1.auth.otp_requests.store'), [
        'mobile' => '98765 43210',
    ])->assertAccepted();

    expect(OtpRequest::query()->sole()->mobile_number_ciphertext)->toBe('+919876543210');
});

test('the OTP request endpoint applies the resend cooldown without issuing another challenge', function () {
    Queue::fake();

    $this->postJson(route('api.v1.auth.otp_requests.store'), [
        'mobile' => '+919876543210',
    ])->assertAccepted();

    $this->postJson(route('api.v1.auth.otp_requests.store'), [
        'mobile' => '+919876543210',
    ])
        ->assertTooManyRequests()
        ->assertHeader('Retry-After', '60')
        ->assertJsonPath('code', 'RATE_LIMITED');

    expect(OtpRequest::query()->count())->toBe(1);
});

test('the OTP request endpoint rejects malformed or unsupported mobile numbers', function (string $mobile) {
    $this->postJson(route('api.v1.auth.otp_requests.store'), [
        'mobile' => $mobile,
    ])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'VALIDATION_ERROR')
        ->assertJsonPath('errors.mobile.0', 'Enter a valid mobile number.');
})->with([
    'malformed' => ['not-a-mobile-number'],
    'unsupported region' => ['+14155552671'],
]);

test('the OTP request endpoint does not accept other HTTP methods', function () {
    $this->getJson(route('api.v1.auth.otp_requests.store'))
        ->assertMethodNotAllowed();
});
