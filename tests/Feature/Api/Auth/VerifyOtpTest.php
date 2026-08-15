<?php

use App\Domain\Auth\Enums\OtpRequestPurpose;
use App\Domain\Auth\Enums\OtpRequestStatus;
use App\Domain\Auth\Services\OtpChallengeIssuer;
use App\Domain\Users\Enums\UserStatus;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

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
        ->assertJsonPath('data.access_token', fn (string $token): bool => $token !== '')
        ->assertJsonMissingPath('data.otp_request_id')
        ->assertJsonMissingPath('data.code');

    expect(User::query()->where('mobile_number', '+919876543210')->count())->toBe(1)
        ->and($issued['challenge']->fresh()->status)->toBe(OtpRequestStatus::Verified)
        ->and(PersonalAccessToken::query()->count())->toBe(1);
});

test('a verified challenge cannot be replayed to create another bearer token', function () {
    $issued = app(OtpChallengeIssuer::class)->issue('+919876543210', OtpRequestPurpose::Authentication);

    $payload = [
        'otp_request_id' => $issued['challenge']->id,
        'code' => $issued['code'],
    ];

    $this->postJson(route('api.v1.auth.otp_verifications.store'), $payload)
        ->assertOk();

    $this->postJson(route('api.v1.auth.otp_verifications.store'), $payload)
        ->assertUnprocessable()
        ->assertJson([
            'success' => false,
            'code' => 'VALIDATION_ERROR',
            'message' => 'The request contains invalid fields.',
            'meta' => [],
        ]);

    expect($issued['challenge']->fresh()->status)->toBe(OtpRequestStatus::Verified)
        ->and(User::query()->count())->toBe(1)
        ->and(PersonalAccessToken::query()->count())->toBe(1);
});

test('an expired OTP verification remains non-disclosing and does not create a session', function () {
    CarbonImmutable::setTestNow('2026-08-15 12:00:00 UTC');
    $issued = app(OtpChallengeIssuer::class)->issue('+919876543210', OtpRequestPurpose::Authentication);

    CarbonImmutable::setTestNow('2026-08-15 12:05:00 UTC');

    $this->postJson(route('api.v1.auth.otp_verifications.store'), [
        'otp_request_id' => $issued['challenge']->id,
        'code' => $issued['code'],
    ])
        ->assertUnprocessable()
        ->assertJson([
            'success' => false,
            'code' => 'VALIDATION_ERROR',
            'message' => 'The request contains invalid fields.',
            'meta' => [],
        ]);

    expect($issued['challenge']->fresh()->status)->toBe(OtpRequestStatus::Expired)
        ->and(User::query()->count())->toBe(0)
        ->and(PersonalAccessToken::query()->count())->toBe(0);
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

    expect($issued['challenge']->fresh()->status)->toBe(OtpRequestStatus::AttemptLimitExhausted)
        ->and($issued['challenge']->fresh()->failed_verification_attempts)->toBe(5)
        ->and(User::query()->count())->toBe(0)
        ->and(PersonalAccessToken::query()->count())->toBe(0);
});

test('a single incorrect code does not prevent a later correct verification', function () {
    $issued = app(OtpChallengeIssuer::class)->issue('+919876543210', OtpRequestPurpose::Authentication);

    $this->postJson(route('api.v1.auth.otp_verifications.store'), [
        'otp_request_id' => $issued['challenge']->id,
        'code' => '000000',
    ])->assertUnprocessable();

    $this->postJson(route('api.v1.auth.otp_verifications.store'), [
        'otp_request_id' => $issued['challenge']->id,
        'code' => $issued['code'],
    ])->assertOk();

    expect($issued['challenge']->fresh()->status)->toBe(OtpRequestStatus::Verified)
        ->and($issued['challenge']->fresh()->failed_verification_attempts)->toBe(1)
        ->and(User::query()->count())->toBe(1)
        ->and(PersonalAccessToken::query()->count())->toBe(1);
});

test('blocked and suspended users cannot establish a bearer-token session', function (UserStatus $status, string $code) {
    $mobileNumber = '+919876543210';
    User::factory()->create([
        'mobile_number' => $mobileNumber,
        'status' => $status,
    ]);
    $issued = app(OtpChallengeIssuer::class)->issue($mobileNumber, OtpRequestPurpose::Authentication);

    $this->postJson(route('api.v1.auth.otp_verifications.store'), [
        'otp_request_id' => $issued['challenge']->id,
        'code' => $issued['code'],
    ])
        ->assertForbidden()
        ->assertJsonPath('success', false)
        ->assertJsonPath('code', $code);

    expect(PersonalAccessToken::query()->count())->toBe(0)
        ->and($issued['challenge']->fresh()->status)->toBe(OtpRequestStatus::PendingDelivery);
})->with([
    'blocked user' => [UserStatus::Blocked, 'USER_BLOCKED'],
    'suspended user' => [UserStatus::Suspended, 'USER_SUSPENDED'],
]);

test('the OTP verification endpoint validates its opaque request ID and six digit code', function () {
    $this->postJson(route('api.v1.auth.otp_verifications.store'), [
        'otp_request_id' => 'not-an-opaque-id',
        'code' => 'abc',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'VALIDATION_ERROR')
        ->assertJsonStructure(['errors' => ['otp_request_id', 'code']]);
});
