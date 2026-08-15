<?php

use App\Domain\Auth\Enums\OtpRequestPurpose;
use App\Domain\Auth\Enums\OtpRequestStatus;
use App\Domain\Auth\Services\OtpChallengeIssuer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);

test('a valid OTP creates a mobile user and issues a bearer token', function () {
    $issued = app(OtpChallengeIssuer::class)->issue('+919876543210', OtpRequestPurpose::Authentication);

    $this->postJson(route('api.v1.auth.otp_verifications.store'), [
        'otp_request_id' => $issued['challenge']->id,
        'code' => $issued['code'],
    ])
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.access_token', fn (string $token): bool => $token !== '');

    expect(User::query()->where('mobile_number', '+919876543210')->count())->toBe(1)
        ->and($issued['challenge']->fresh()->status)->toBe(OtpRequestStatus::Verified)
        ->and(PersonalAccessToken::query()->count())->toBe(1);
});

test('an OTP verification does not disclose invalid or exhausted challenge state', function () {
    $issued = app(OtpChallengeIssuer::class)->issue('+919876543210', OtpRequestPurpose::Authentication);

    foreach (range(1, 5) as $_) {
        $this->postJson(route('api.v1.auth.otp_verifications.store'), [
            'otp_request_id' => $issued['challenge']->id,
            'code' => '000000',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_ERROR');
    }

    expect(User::query()->count())->toBe(0)
        ->and(PersonalAccessToken::query()->count())->toBe(0);
});

test('the OTP verification endpoint validates its opaque request ID and six digit code', function () {
    $this->postJson(route('api.v1.auth.otp_verifications.store'), [
        'otp_request_id' => 'not-an-opaque-id',
        'code' => 'abc',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'VALIDATION_ERROR')
        ->assertJsonStructure(['errors' => ['otp_request_id', 'code']]);
});
