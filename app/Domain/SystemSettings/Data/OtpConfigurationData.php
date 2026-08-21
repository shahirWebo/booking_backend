<?php

namespace App\Domain\SystemSettings\Data;

final readonly class OtpConfigurationData
{
    public function __construct(
        public int $codeLifetimeSeconds,
        public int $resendCooldownSeconds,
        public int $maxVerificationAttempts,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            codeLifetimeSeconds: (int) $attributes['code_lifetime_seconds'],
            resendCooldownSeconds: (int) $attributes['resend_cooldown_seconds'],
            maxVerificationAttempts: (int) $attributes['max_verification_attempts'],
        );
    }

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'code_lifetime_seconds' => $this->codeLifetimeSeconds,
            'resend_cooldown_seconds' => $this->resendCooldownSeconds,
            'max_verification_attempts' => $this->maxVerificationAttempts,
        ];
    }
}
