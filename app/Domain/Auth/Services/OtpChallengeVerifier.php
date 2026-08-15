<?php

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Enums\OtpRequestStatus;
use App\Domain\Auth\Exceptions\OtpAttemptsExceededException;
use App\Domain\Auth\Exceptions\OtpInvalidOrExpiredException;
use App\Models\OtpRequest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class OtpChallengeVerifier
{
    public function __construct(private readonly OtpCodeHasher $codeHasher) {}

    /**
     * Atomically consume a valid challenge. Authentication/token issuance stays
     * with the verification endpoint task that calls this service.
     *
     * @throws OtpAttemptsExceededException|OtpInvalidOrExpiredException
     */
    public function verify(string $challengeId, string $code): OtpRequest
    {
        $result = DB::transaction(function () use ($challengeId, $code): OtpRequest|string {
            $challenge = OtpRequest::query()->lockForUpdate()->find($challengeId);

            if ($challenge === null) {
                return 'invalid';
            }

            $now = CarbonImmutable::now('UTC');

            if ($challenge->expires_at->lessThanOrEqualTo($now)) {
                if (in_array($challenge->status, $this->verifiableStatuses(), true)) {
                    $challenge->forceFill([
                        'status' => OtpRequestStatus::Expired,
                        'terminal_reason' => 'expired',
                    ])->save();
                }

                return 'invalid';
            }

            if (! in_array($challenge->status, $this->verifiableStatuses(), true)) {
                if ($challenge->status === OtpRequestStatus::AttemptLimitExhausted) {
                    return 'attempts_exceeded';
                }

                return 'invalid';
            }

            if ($challenge->failed_verification_attempts >= config('otp.max_verification_attempts')) {
                $challenge->forceFill([
                    'status' => OtpRequestStatus::AttemptLimitExhausted,
                    'terminal_reason' => 'attempt_limit_exhausted',
                ])->save();

                return 'attempts_exceeded';
            }

            if (! $this->codeHasher->verify($challenge->id, $code, $challenge->code_hash)) {
                $attempts = $challenge->failed_verification_attempts + 1;
                $challenge->failed_verification_attempts = $attempts;

                if ($attempts >= config('otp.max_verification_attempts')) {
                    $challenge->status = OtpRequestStatus::AttemptLimitExhausted;
                    $challenge->terminal_reason = 'attempt_limit_exhausted';
                }

                $challenge->save();

                if ($attempts >= config('otp.max_verification_attempts')) {
                    return 'attempts_exceeded';
                }

                return 'invalid';
            }

            $challenge->forceFill([
                'status' => OtpRequestStatus::Verified,
                'consumed_at' => $now,
            ])->save();

            return $challenge;
        });

        if ($result === 'attempts_exceeded') {
            throw new OtpAttemptsExceededException;
        }

        if ($result === 'invalid') {
            throw new OtpInvalidOrExpiredException;
        }

        return $result;
    }

    /**
     * @return list<OtpRequestStatus>
     */
    private function verifiableStatuses(): array
    {
        return [
            OtpRequestStatus::PendingDelivery,
            OtpRequestStatus::ProviderAccepted,
            OtpRequestStatus::DeliveryUnknown,
        ];
    }
}
