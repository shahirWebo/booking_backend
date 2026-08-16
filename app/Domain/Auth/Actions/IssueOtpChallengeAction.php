<?php

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Enums\OtpRequestPurpose;
use App\Domain\Auth\Enums\OtpRequestStatus;
use App\Domain\Auth\Exceptions\OtpResendCooldownException;
use App\Domain\Auth\Repositories\OtpRequestRepository;
use App\Domain\Auth\Services\AuthenticationAuditLogger;
use App\Domain\Auth\Services\OtpCodeGenerator;
use App\Domain\Auth\Services\OtpCodeHasher;
use App\Domain\Auth\Services\OtpPrivacyKeyDeriver;
use App\Models\OtpRequest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class IssueOtpChallengeAction
{
    public function __construct(
        private readonly OtpRequestRepository $otpRequests,
        private readonly OtpCodeGenerator $codeGenerator,
        private readonly OtpCodeHasher $codeHasher,
        private readonly OtpPrivacyKeyDeriver $keyDeriver,
        private readonly AuthenticationAuditLogger $auditLogger,
    ) {}

    /**
     * @return array{challenge: OtpRequest, code: string}
     *
     * @throws OtpResendCooldownException
     */
    public function execute(string $mobileNumber, OtpRequestPurpose $purpose): array
    {
        $issued = DB::transaction(function () use ($mobileNumber, $purpose): array {
            $now = CarbonImmutable::now('UTC');
            $lookup = $this->keyDeriver->mobileLookup($mobileNumber, $purpose->value);
            $activeStatuses = $this->activeStatuses();

            $this->otpRequests->expireActive($lookup, $purpose, $activeStatuses, $now);

            $latestRequest = $this->otpRequests->latestForUpdate($lookup, $purpose);

            if ($latestRequest !== null && $latestRequest->resend_available_at->isFuture()) {
                $secondsUntilResend = $now->diffInSeconds($latestRequest->resend_available_at, false);

                throw new OtpResendCooldownException(
                    $secondsUntilResend > 1 ? (int) ceil($secondsUntilResend) : 1,
                );
            }

            $this->otpRequests->supersedeActive($lookup, $purpose, $activeStatuses, $now);

            $challengeId = (string) Str::ulid();
            $code = config('app.env') === 'local' ? 123456 : $this->codeGenerator->generate();
            $challenge = $this->otpRequests->create($challengeId, [
                'purpose' => $purpose,
                'schema_version' => 1,
                'mobile_number_hash_key_version' => 1,
                'mobile_number_lookup_hmac' => $lookup,
                'mobile_number_ciphertext' => $mobileNumber,
                'code_hash' => $this->codeHasher->hash($challengeId, $code),
                'status' => OtpRequestStatus::PendingDelivery,
                'failed_verification_attempts' => 0,
                'delivery_effect_key' => hash('sha256', 'otp_delivery:'.Str::ulid()),
                'audit_correlation_id' => (string) Str::ulid(),
                'issued_at' => $now,
                'expires_at' => $now->addSeconds(config('otp.code_lifetime_seconds')),
                'resend_available_at' => $now->addSeconds(config('otp.resend_cooldown_seconds')),
            ]);

            return ['challenge' => $challenge, 'code' => $code];
        });

        $this->auditLogger->challengeIssued($issued['challenge']);

        return $issued;
    }

    /**
     * @return list<OtpRequestStatus>
     */
    private function activeStatuses(): array
    {
        return [
            OtpRequestStatus::PendingDelivery,
            OtpRequestStatus::ProviderAccepted,
            OtpRequestStatus::DeliveryUnknown,
        ];
    }
}
