<?php

namespace App\Domain\Auth\Providers;

use App\Domain\Auth\Contracts\OtpDeliveryProvider;
use App\Domain\Auth\Data\FakeOtpDelivery;
use App\Domain\Auth\Data\OtpDeliveryOutcome;
use App\Domain\Auth\Data\OtpDeliveryRequest;
use App\Domain\Auth\Data\OtpDeliveryResult;
use LogicException;

/**
 * Deterministic local/test adapter. It deliberately never logs or retains a
 * destination, rendered message, or code outside the test-only accessor.
 */
final class FakeOtpDeliveryProvider implements OtpDeliveryProvider
{
    /** @var array<string, FakeOtpDelivery> */
    private array $deliveries = [];

    /** @var array<string, string> */
    private array $testCodes = [];

    public function send(OtpDeliveryRequest $request): OtpDeliveryResult
    {
        $outcome = OtpDeliveryOutcome::tryFrom((string) config('otp.fake_delivery_outcome'));

        if ($outcome === null) {
            throw new LogicException('OTP_FAKE_DELIVERY_OUTCOME must be a supported delivery outcome.');
        }

        $providerReference = 'fake:'.$request->challengeId;
        $this->deliveries[$request->challengeId] = new FakeOtpDelivery(
            $request->challengeId,
            $outcome,
            $providerReference,
        );

        if (app()->runningUnitTests()) {
            $this->testCodes[$request->challengeId] = $request->code;
        }

        return new OtpDeliveryResult(
            $outcome,
            $providerReference,
            retryAfterSeconds: $outcome === OtpDeliveryOutcome::TransientFailure ? 5 : null,
        );
    }

    /** @return array<string, FakeOtpDelivery> */
    public function deliveries(): array
    {
        return $this->deliveries;
    }

    public function testCodeFor(string $challengeId): ?string
    {
        if (! app()->runningUnitTests()) {
            throw new LogicException('Fake OTP codes are available only to isolated tests.');
        }

        return $this->testCodes[$challengeId] ?? null;
    }
}
