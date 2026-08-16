<?php

namespace App\Jobs;

use App\Domain\Auth\Contracts\OtpDeliveryProvider;
use App\Domain\Auth\Data\OtpDeliveryOutcome;
use App\Domain\Auth\Data\OtpDeliveryRequest;
use App\Domain\Auth\Enums\OtpRequestStatus;
use App\Domain\Auth\Repositories\OtpRequestRepository;
use App\Models\OtpRequest;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

final class SendOtpChallenge implements ShouldBeEncrypted, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    /** @var list<int> */
    public array $backoff = [5, 30];

    public function __construct(
        public readonly string $challengeId,
        private readonly string $code,
    ) {}

    public function handle(OtpDeliveryProvider $provider, OtpRequestRepository $otpRequests): void
    {
        $challenge = $otpRequests->find($this->challengeId);

        if (! $challenge instanceof OtpRequest || ! $this->isSendable($challenge)) {
            return;
        }

        try {
            $result = $provider->send(new OtpDeliveryRequest(
                $challenge->id,
                $challenge->mobile_number_ciphertext,
                $this->code,
                CarbonImmutable::instance($challenge->expires_at),
                (string) config('otp.delivery_locale'),
                (string) config('otp.delivery_sender_profile'),
                $challenge->delivery_effect_key,
                request()->attributes->get('request_id'),
            ));
        } catch (Throwable) {
            $this->markUnknown($otpRequests);

            return;
        }

        if ($result->outcome === OtpDeliveryOutcome::TransientFailure) {
            $this->release($result->retryAfterSeconds ?? 5);

            return;
        }

        $status = match ($result->outcome) {
            OtpDeliveryOutcome::Accepted => OtpRequestStatus::ProviderAccepted,
            OtpDeliveryOutcome::PermanentFailure => OtpRequestStatus::DeliveryFailed,
            OtpDeliveryOutcome::Unknown => OtpRequestStatus::DeliveryUnknown,
        };

        $terminalReason = $status === OtpRequestStatus::DeliveryFailed ? 'provider_permanent_failure' : null;

        DB::transaction(function () use ($status, $terminalReason, $result, $otpRequests): void {
            $challenge = $otpRequests->findForUpdate($this->challengeId);

            if (! $challenge instanceof OtpRequest || ! $this->isSendable($challenge)) {
                return;
            }

            $challenge->forceFill([
                'status' => $status,
                'terminal_reason' => $terminalReason,
                'delivery_provider_key' => (string) config('otp.delivery_provider'),
                'delivery_provider_reference' => $result->providerReference,
            ]);
            $otpRequests->save($challenge);
        });
    }

    private function markUnknown(OtpRequestRepository $otpRequests): void
    {
        DB::transaction(function () use ($otpRequests): void {
            $challenge = $otpRequests->findForUpdate($this->challengeId);

            if (! $challenge instanceof OtpRequest || ! $this->isSendable($challenge)) {
                return;
            }

            $challenge->forceFill([
                'status' => OtpRequestStatus::DeliveryUnknown,
                'delivery_provider_key' => (string) config('otp.delivery_provider'),
            ]);
            $otpRequests->save($challenge);
        });
    }

    private function isSendable(OtpRequest $challenge): bool
    {
        return $challenge->status === OtpRequestStatus::PendingDelivery
            && $challenge->expires_at->isFuture();
    }
}
