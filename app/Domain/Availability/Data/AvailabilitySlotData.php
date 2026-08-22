<?php

namespace App\Domain\Availability\Data;

use Carbon\CarbonImmutable;

final readonly class AvailabilitySlotData
{
    public function __construct(
        public CarbonImmutable $startsAt,
        public CarbonImmutable $endsAt,
        public string $locationTimezone,
    ) {}

    /**
     * @return array{starts_at: string, ends_at: string, local_date: string, starts_at_time: string, ends_at_time: string, location_timezone: string}
     */
    public function toArray(): array
    {
        $localStart = $this->startsAt->setTimezone($this->locationTimezone);
        $localEnd = $this->endsAt->setTimezone($this->locationTimezone);

        return [
            'starts_at' => $this->startsAt->utc()->format('Y-m-d\\TH:i:s.u\\Z'),
            'ends_at' => $this->endsAt->utc()->format('Y-m-d\\TH:i:s.u\\Z'),
            'local_date' => $localStart->format('Y-m-d'),
            'starts_at_time' => $localStart->format('H:i:s'),
            'ends_at_time' => $localEnd->format('H:i:s'),
            'location_timezone' => $this->locationTimezone,
        ];
    }
}
