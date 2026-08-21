<?php

namespace App\Domain\SystemSettings\Data;

final readonly class PlatformSupportData
{
    public function __construct(
        public string $supportEmail,
        public string $supportPhoneE164,
        public string $supportHours,
        public string $supportTimezone,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            supportEmail: (string) $attributes['support_email'],
            supportPhoneE164: (string) $attributes['support_phone_e164'],
            supportHours: (string) $attributes['support_hours'],
            supportTimezone: (string) $attributes['support_timezone'],
        );
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'support_email' => $this->supportEmail,
            'support_phone_e164' => $this->supportPhoneE164,
            'support_hours' => $this->supportHours,
            'support_timezone' => $this->supportTimezone,
        ];
    }
}
