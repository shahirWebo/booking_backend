<?php

use App\Domain\Auth\Actions\IssueOtpChallengeAction;
use App\Domain\Auth\Enums\OtpRequestPurpose;
use App\Domain\Users\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;

uses(RefreshDatabase::class);

test('issuing an authentication challenge records a minimized audit event', function () {
    Log::spy();

    $issued = app(IssueOtpChallengeAction::class)->execute('+919876543210', OtpRequestPurpose::Authentication);

    Log::shouldHaveReceived('info')
        ->once()
        ->with('auth.challenge.issued', Mockery::on(function (array $context) use ($issued): bool {
            return $context === [
                'challenge_id' => $issued['challenge']->id,
                'audit_correlation_id' => $issued['challenge']->audit_correlation_id,
                'purpose' => 'authentication',
            ];
        }));
});

test('successful authentication records opaque challenge and user identifiers only', function () {
    $issued = app(IssueOtpChallengeAction::class)->execute('+919876543210', OtpRequestPurpose::Authentication);
    Log::spy();

    $this->postJson(route('api.v1.auth.otp_verifications.store'), [
        'otp_request_id' => $issued['challenge']->id,
        'code' => $issued['code'],
    ])->assertOk();

    $user = User::query()->sole();

    Log::shouldHaveReceived('info')
        ->once()
        ->with('auth.authentication.succeeded', Mockery::on(function (array $context) use ($issued, $user): bool {
            return $context === [
                'challenge_id' => $issued['challenge']->id,
                'audit_correlation_id' => $issued['challenge']->audit_correlation_id,
                'user_id' => $user->id,
            ];
        }));
});

test('failed OTP verification records a non-disclosing audit outcome', function () {
    $issued = app(IssueOtpChallengeAction::class)->execute('+919876543210', OtpRequestPurpose::Authentication);
    Log::spy();

    $this->postJson(route('api.v1.auth.otp_verifications.store'), [
        'otp_request_id' => $issued['challenge']->id,
        'code' => '000000',
    ])->assertUnprocessable();

    Log::shouldHaveReceived('warning')
        ->once()
        ->with('auth.authentication.failed', [
            'challenge_id' => $issued['challenge']->id,
            'outcome' => 'invalid_or_expired',
        ]);
});

test('restricted authentication and logout record session audit events', function () {
    $user = User::factory()->create(['status' => UserStatus::Blocked]);
    $issued = app(IssueOtpChallengeAction::class)->execute($user->mobile_number, OtpRequestPurpose::Authentication);
    Log::spy();

    $this->postJson(route('api.v1.auth.otp_verifications.store'), [
        'otp_request_id' => $issued['challenge']->id,
        'code' => $issued['code'],
    ])->assertForbidden();

    Log::shouldHaveReceived('warning')
        ->once()
        ->with('auth.authentication.restricted', [
            'challenge_id' => $issued['challenge']->id,
            'audit_correlation_id' => $issued['challenge']->audit_correlation_id,
            'user_id' => $user->id,
            'account_status' => 'blocked',
        ]);

    $activeUser = User::factory()->create(['status' => UserStatus::Active]);
    $token = $activeUser->createToken('current-device');

    $this->withToken($token->plainTextToken)
        ->deleteJson(route('api.v1.auth.session.destroy'))
        ->assertNoContent();

    Log::shouldHaveReceived('info')
        ->once()
        ->with('auth.session.logged_out', ['user_id' => $activeUser->id]);
});

test('a restricted bearer-token user records a session revocation audit event', function () {
    $user = User::factory()->create(['status' => UserStatus::Active]);
    $token = $user->createToken('current-device');
    $user->update(['status' => UserStatus::Suspended]);
    Log::spy();

    $this->withToken($token->plainTextToken)
        ->getJson(route('api.v1.auth.user.show'))
        ->assertForbidden();

    Log::shouldHaveReceived('warning')
        ->once()
        ->with('auth.session.revoked_restricted_user', [
            'user_id' => $user->id,
            'account_status' => 'suspended',
        ]);
});
