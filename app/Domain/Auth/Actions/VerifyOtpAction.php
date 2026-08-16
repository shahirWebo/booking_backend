<?php

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Enums\OtpRequestStatus;
use App\Domain\Auth\Exceptions\AccountAccessRestrictedException;
use App\Domain\Auth\Exceptions\OtpAttemptsExceededException;
use App\Domain\Auth\Exceptions\OtpInvalidOrExpiredException;
use App\Domain\Auth\Repositories\OtpRequestRepository;
use App\Domain\Auth\Repositories\UserRepository;
use App\Domain\Auth\Services\AuthenticationAuditLogger;
use App\Domain\Auth\Services\OtpCodeHasher;
use App\Domain\Users\Enums\UserStatus;
use App\Models\OtpRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\NewAccessToken;
use LogicException;

final class VerifyOtpAction
{
    public function __construct(
        private readonly OtpRequestRepository $otpRequests,
        private readonly UserRepository $users,
        private readonly OtpCodeHasher $codeHasher,
        private readonly AuthenticationAuditLogger $auditLogger,
    ) {}

    /**
     * @return array{user: User, accessToken: NewAccessToken}
     *
     * @throws OtpAttemptsExceededException|OtpInvalidOrExpiredException
     */
    public function execute(string $challengeId, string $code): array
    {
        $result = DB::transaction(function () use ($challengeId, $code): array|string {
            $challenge = $this->otpRequests->findForUpdate($challengeId);

            if ($challenge === null) {
                return 'invalid';
            }

            $now = CarbonImmutable::now('UTC');

            if ($challenge->expires_at->lessThanOrEqualTo($now)) {
                if ($this->isVerifiable($challenge)) {
                    $challenge->forceFill([
                        'status' => OtpRequestStatus::Expired,
                        'terminal_reason' => 'expired',
                    ]);
                    $this->otpRequests->save($challenge);
                }

                return 'invalid';
            }

            if (! $this->isVerifiable($challenge)) {
                return $challenge->status === OtpRequestStatus::AttemptLimitExhausted
                    ? 'attempts_exceeded'
                    : 'invalid';
            }

            if ($challenge->failed_verification_attempts >= config('otp.max_verification_attempts')) {
                $challenge->forceFill([
                    'status' => OtpRequestStatus::AttemptLimitExhausted,
                    'terminal_reason' => 'attempt_limit_exhausted',
                ]);
                $this->otpRequests->save($challenge);

                return 'attempts_exceeded';
            }

            if (! $this->codeHasher->verify($challenge->id, $code, $challenge->code_hash)) {
                return $this->recordFailedAttempt($challenge);
            }

            $challenge->forceFill([
                'status' => OtpRequestStatus::Verified,
                'consumed_at' => $now,
            ]);
            $this->otpRequests->save($challenge);

            $user = $this->users->findOrCreateByMobile($challenge->mobile_number_ciphertext);

            if ($user->status !== UserStatus::Active) {
                $this->auditLogger->authenticationRestricted($challenge, $user);

                throw new AccountAccessRestrictedException($user->status);
            }

            return [
                'challenge' => $challenge,
                'user' => $user,
                'accessToken' => $this->users->createAccessToken($user),
            ];
        });

        if ($result === 'attempts_exceeded') {
            $this->auditLogger->authenticationFailed($challengeId, 'attempt_limit_exhausted');

            throw new OtpAttemptsExceededException;
        }

        if ($result === 'invalid') {
            $this->auditLogger->authenticationFailed($challengeId, 'invalid_or_expired');

            throw new OtpInvalidOrExpiredException;
        }

        if (! is_array($result)) {
            throw new LogicException('OTP verification returned an unsupported outcome.');
        }

        $this->auditLogger->authenticationSucceeded($result['challenge'], $result['user']);

        return [
            'user' => $result['user'],
            'accessToken' => $result['accessToken'],
        ];
    }

    private function recordFailedAttempt(OtpRequest $challenge): string
    {
        $attempts = $challenge->failed_verification_attempts + 1;
        $challenge->failed_verification_attempts = $attempts;

        if ($attempts >= config('otp.max_verification_attempts')) {
            $challenge->status = OtpRequestStatus::AttemptLimitExhausted;
            $challenge->terminal_reason = 'attempt_limit_exhausted';
        }

        $this->otpRequests->save($challenge);

        return $attempts >= config('otp.max_verification_attempts')
            ? 'attempts_exceeded'
            : 'invalid';
    }

    private function isVerifiable(OtpRequest $challenge): bool
    {
        return in_array($challenge->status, [
            OtpRequestStatus::PendingDelivery,
            OtpRequestStatus::ProviderAccepted,
            OtpRequestStatus::DeliveryUnknown,
        ], true);
    }
}
