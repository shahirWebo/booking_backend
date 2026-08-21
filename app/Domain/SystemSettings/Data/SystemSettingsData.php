<?php

namespace App\Domain\SystemSettings\Data;

final readonly class SystemSettingsData
{
    public function __construct(
        public BookingConfigurationData $booking,
        public OtpConfigurationData $otp,
        public PlatformSupportData $support,
    ) {}

    /**
     * @return array{
     *     booking: array<string, int>,
     *     otp: array<string, int>,
     *     support: array<string, string>
     * }
     */
    public function toArray(): array
    {
        return [
            'booking' => $this->booking->toArray(),
            'otp' => $this->otp->toArray(),
            'support' => $this->support->toArray(),
        ];
    }
}
