<?php

namespace App\Domain\Auth\Data;

final readonly class FakeOtpDelivery
{
    public function __construct(
        public string $challengeId,
        public OtpDeliveryOutcome $outcome,
        public ?string $providerReference,
    ) {}
}
