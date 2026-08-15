<?php

namespace App\Domain\Auth\Data;

use Carbon\CarbonImmutable;

final readonly class OtpDeliveryRequest
{
    public function __construct(
        public string $challengeId,
        public string $destination,
        public string $code,
        public CarbonImmutable $expiresAt,
        public string $locale,
        public string $senderProfile,
        public string $idempotencyKey,
        public ?string $traceId = null,
    ) {}
}
