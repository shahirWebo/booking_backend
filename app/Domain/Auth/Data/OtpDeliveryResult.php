<?php

namespace App\Domain\Auth\Data;

final readonly class OtpDeliveryResult
{
    public function __construct(
        public OtpDeliveryOutcome $outcome,
        public ?string $providerReference = null,
        public ?string $safeCode = null,
        public ?int $retryAfterSeconds = null,
    ) {}
}
