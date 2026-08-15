<?php

use App\Domain\Auth\Data\OtpDeliveryOutcome;
use App\Domain\Auth\Data\OtpDeliveryRequest;
use App\Domain\Auth\Data\OtpDeliveryResult;
use App\Domain\Auth\Enums\OtpRequestPurpose;
use App\Domain\Auth\Enums\OtpRequestStatus;
use App\Domain\Auth\Exceptions\OtpAttemptsExceededException;
use App\Domain\Auth\Exceptions\OtpInvalidOrExpiredException;
use App\Domain\Auth\Exceptions\OtpRateLimitExceededException;
use App\Domain\Auth\Exceptions\OtpResendCooldownException;
use App\Domain\Auth\Services\OtpChallengeIssuer;
use App\Domain\Auth\Services\OtpChallengeVerifier;
use App\Domain\Auth\Services\OtpPrivacyKeyDeriver;
use App\Domain\Auth\Services\OtpRequestRateLimiter;
use App\Models\OtpRequest;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

uses(RefreshDatabase::class);

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

function persistedOtpRequest(string $id): OtpRequest
{
    $challenge = OtpRequest::query()->whereKey($id)->first();

    if (! $challenge instanceof OtpRequest) {
        throw new RuntimeException('Expected OTP challenge to have been persisted.');
    }

    return $challenge;
}

test('an OTP challenge expires by authoritative UTC time before verification', function () {
    CarbonImmutable::setTestNow('2026-08-15 12:00:00 UTC');
    $issued = app(OtpChallengeIssuer::class)->issue('+919876543210', OtpRequestPurpose::Authentication);

    CarbonImmutable::setTestNow('2026-08-15 12:05:00 UTC');

    expect(fn () => app(OtpChallengeVerifier::class)->verify($issued['challenge']->id, $issued['code']))
        ->toThrow(OtpInvalidOrExpiredException::class);

    $persistedChallenge = persistedOtpRequest($issued['challenge']->id);

    expect($persistedChallenge->getAttribute('status'))->toBe(OtpRequestStatus::Expired)
        ->and($persistedChallenge->getAttribute('terminal_reason'))->toBe('expired');
});

test('an accepted resend is blocked during the cooldown then supersedes the old challenge', function () {
    CarbonImmutable::setTestNow('2026-08-15 12:00:00 UTC');
    $issuer = app(OtpChallengeIssuer::class);
    $first = $issuer->issue('+919876543210', OtpRequestPurpose::Authentication);

    expect(fn () => $issuer->issue('+919876543210', OtpRequestPurpose::Authentication))
        ->toThrow(OtpResendCooldownException::class);

    CarbonImmutable::setTestNow('2026-08-15 12:01:00 UTC');
    $second = $issuer->issue('+919876543210', OtpRequestPurpose::Authentication);

    expect($first['challenge']->fresh()->status)->toBe(OtpRequestStatus::Superseded)
        ->and($second['challenge']->id)->not->toBe($first['challenge']->id)
        ->and($second['challenge']->expires_at->toIso8601String())->toBe('2026-08-15T12:06:00+00:00');
});

test('the fifth invalid verification permanently exhausts the OTP challenge', function () {
    $issued = app(OtpChallengeIssuer::class)->issue('+919876543210', OtpRequestPurpose::Authentication);
    $verifier = app(OtpChallengeVerifier::class);

    foreach (range(1, 4) as $_) {
        expect(fn () => $verifier->verify($issued['challenge']->id, '000000'))
            ->toThrow(OtpInvalidOrExpiredException::class);
    }

    expect(fn () => $verifier->verify($issued['challenge']->id, '000000'))
        ->toThrow(OtpAttemptsExceededException::class);
    expect(fn () => $verifier->verify($issued['challenge']->id, $issued['code']))
        ->toThrow(OtpAttemptsExceededException::class);

    $persistedChallenge = persistedOtpRequest($issued['challenge']->id);

    expect($persistedChallenge->getAttribute('status'))->toBe(OtpRequestStatus::AttemptLimitExhausted)
        ->and($persistedChallenge->getAttribute('failed_verification_attempts'))->toBe(5);
});

test('mobile, source IP, and installation limits are independently enforced with private keys', function () {
    $limiter = app(OtpRequestRateLimiter::class);

    foreach (range(1, 3) as $_) {
        $limiter->consume('+919876543210', 'authentication', '203.0.113.10', '01JABCDEF0123456789ABCDE');
    }

    expect(fn () => $limiter->consume('+919876543210', 'authentication', '203.0.113.11', '01JABCDEF0123456789ABCDF'))
        ->toThrow(OtpRateLimitExceededException::class);

    RateLimiter::clear('v1:auth:otp_request:mobile_15_minutes:'.app(OtpPrivacyKeyDeriver::class)->mobileLookup('+919876543210', 'authentication'));
    $limiter->consume('+919876543210', 'authentication', '203.0.113.12', '01JABCDEF0123456789ABCDE');
});

test('the provider port carries only controlled delivery semantics', function () {
    $request = new OtpDeliveryRequest(
        '01JABCDEF0123456789ABCDE',
        '+919876543210',
        '012345',
        CarbonImmutable::now('UTC')->addMinutes(5),
        'en',
        'default',
        'opaque-effect-key',
    );
    $result = new OtpDeliveryResult(OtpDeliveryOutcome::Accepted, 'safe-reference');

    expect($request->challengeId)->toBe('01JABCDEF0123456789ABCDE')
        ->and($result->outcome)->toBe(OtpDeliveryOutcome::Accepted)
        ->and($result->providerReference)->toBe('safe-reference');
});
