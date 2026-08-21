<?php

namespace App\Domain\SystemSettings\Data;

final readonly class BookingConfigurationData
{
    public function __construct(
        public int $bookingHoldMinutes,
        public int $cancellationCutoffHours,
        public int $maxAdvanceBookingDays,
        public int $minSlotDurationMinutes,
        public int $maxBookingDurationMinutes,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            bookingHoldMinutes: (int) $attributes['booking_hold_minutes'],
            cancellationCutoffHours: (int) $attributes['cancellation_cutoff_hours'],
            maxAdvanceBookingDays: (int) $attributes['max_advance_booking_days'],
            minSlotDurationMinutes: (int) $attributes['min_slot_duration_minutes'],
            maxBookingDurationMinutes: (int) $attributes['max_booking_duration_minutes'],
        );
    }

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'booking_hold_minutes' => $this->bookingHoldMinutes,
            'cancellation_cutoff_hours' => $this->cancellationCutoffHours,
            'max_advance_booking_days' => $this->maxAdvanceBookingDays,
            'min_slot_duration_minutes' => $this->minSlotDurationMinutes,
            'max_booking_duration_minutes' => $this->maxBookingDurationMinutes,
        ];
    }
}
