<?php

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Enums\OtpRequestPurpose;
use App\Domain\Auth\Enums\OtpRequestStatus;
use App\Domain\Auth\Exceptions\OtpResendCooldownException;
use App\Models\OtpRequest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class OtpChallengeIssuer
{
    public function __construct(
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
    public function issue(string $mobileNumber, OtpRequestPurpose $purpose): array
    {
        $issued = DB::transaction(function () use ($mobileNumber, $purpose): array {
            $now = CarbonImmutable::now('UTC');
            $lookup = $this->keyDeriver->mobileLookup($mobileNumber, $purpose->value);
            $activeStatuses = [
                OtpRequestStatus::PendingDelivery->value,
                OtpRequestStatus::ProviderAccepted->value,
                OtpRequestStatus::DeliveryUnknown->value,
            ];

            OtpRequest::query()
                ->where('mobile_number_lookup_hmac', $lookup)
                ->where('purpose', $purpose->value)
                ->whereIn('status', $activeStatuses)
                ->where('expires_at', '<=', $now)
                ->lockForUpdate()
                ->update([
                    'status' => OtpRequestStatus::Expired->value,
                    'terminal_reason' => 'expired',
                    'updated_at' => $now,
                ]);

            $latestRequest = OtpRequest::query()
                ->where('mobile_number_lookup_hmac', $lookup)
                ->where('purpose', $purpose->value)
                ->latest('issued_at')
                ->lockForUpdate()
                ->first();

            if ($latestRequest !== null && $latestRequest->resend_available_at->isFuture()) {
                $secondsUntilResend = $now->diffInSeconds($latestRequest->resend_available_at, false);

                throw new OtpResendCooldownException(
                    $secondsUntilResend > 1 ? (int) ceil($secondsUntilResend) : 1,
                );
            }

            OtpRequest::query()
                ->where('mobile_number_lookup_hmac', $lookup)
                ->where('purpose', $purpose->value)
                ->whereIn('status', $activeStatuses)
                ->lockForUpdate()
                ->update([
                    'status' => OtpRequestStatus::Superseded->value,
                    'terminal_reason' => 'resend',
                    'updated_at' => $now,
                ]);

            $challenge = new OtpRequest([
                'purpose' => $purpose,
                'schema_version' => 1,
                'mobile_number_hash_key_version' => 1,
                'mobile_number_lookup_hmac' => $lookup,
                'mobile_number_ciphertext' => $mobileNumber,
                'status' => OtpRequestStatus::PendingDelivery,
                'failed_verification_attempts' => 0,
                'delivery_effect_key' => hash('sha256', 'otp_delivery:'.Str::ulid()),
                'audit_correlation_id' => (string) Str::ulid(),
                'issued_at' => $now,
                'expires_at' => $now->addSeconds(config('otp.code_lifetime_seconds')),
                'resend_available_at' => $now->addSeconds(config('otp.resend_cooldown_seconds')),
            ]);
            $challenge->id = (string) Str::ulid();
            $code = $this->codeGenerator->generate();
            $challenge->code_hash = $this->codeHasher->hash($challenge->id, $code);
            $challenge->save();

            return ['challenge' => $challenge, 'code' => $code];
        });

        $this->auditLogger->challengeIssued($issued['challenge']);

        return $issued;
    }
}
