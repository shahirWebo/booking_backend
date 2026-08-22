<?php

namespace App\Domain\Availability\Services;

use App\Domain\Availability\Data\AvailabilitySlotData;
use App\Models\AvailabilityTimeRange;
use App\Models\MaintenanceBlock;
use App\Models\SlotBlock;
use App\Models\Turf;
use Carbon\CarbonImmutable;
use DateTimeZone;
use InvalidArgumentException;

final class AvailabilityService
{
    /**
     * Compile location-local recurring availability into bookable UTC intervals for one local date.
     *
     * @return list<AvailabilitySlotData>
     */
    public function slotsForDate(Turf $turf, string $date, CarbonImmutable $now): array
    {
        $turf->loadMissing(['location', 'availabilityRules.timeRanges']);

        $timezone = $turf->location->timezone;
        $localDate = $this->parseLocalDate($date, $timezone);
        $dayStart = $this->resolveLocalBoundary($date, '00:00:00', $timezone, false);
        $dayEnd = $this->resolveLocalBoundary($localDate->addDay()->format('Y-m-d'), '00:00:00', $timezone, false);

        if ($dayStart === null || $dayEnd === null || ! $this->isWithinBookingWindow($turf, $localDate, $now, $timezone)) {
            return [];
        }

        if ($turf->location->holidays()->where('holiday_date', $date)->where('is_closed', true)->exists()) {
            return [];
        }

        $blocks = $turf->slotBlocks()
            ->whereIn('block_date', [$localDate->subDay()->format('Y-m-d'), $date])
            ->get();

        if ($blocks->contains(fn (SlotBlock $block): bool => $block->block_date === $date && $block->is_full_day)) {
            return [];
        }

        $maintenanceBlocks = $turf->maintenanceBlocks()
            ->where('starts_at', '<', $dayEnd)
            ->where('ends_at', '>', $dayStart)
            ->get();

        $slots = [];
        $minimumStart = $now->utc()->addMinutes($turf->booking_lead_time_minutes);

        foreach ($this->scheduleRangesForDate($turf, $localDate) as [$rangeDate, $range]) {
            $startsAt = $this->resolveLocalBoundary($rangeDate, $range->starts_at_time, $timezone, false);
            $endsAt = $this->resolveLocalBoundary(
                $range->ends_next_day ? $this->parseLocalDate($rangeDate, $timezone)->addDay()->format('Y-m-d') : $rangeDate,
                $range->ends_at_time,
                $timezone,
                true,
            );

            if ($startsAt === null || $endsAt === null || $endsAt->lessThanOrEqualTo($startsAt)) {
                continue;
            }

            for ($slotStart = $startsAt; $slotStart->lessThan($endsAt); $slotStart = $slotStart->addMinutes($turf->default_slot_duration_minutes)) {
                $slotEnd = $slotStart->addMinutes($turf->default_slot_duration_minutes);

                if ($slotEnd->greaterThan($endsAt) || $slotStart->lessThan($dayStart) || $slotStart->greaterThanOrEqualTo($dayEnd)) {
                    continue;
                }

                if ($slotStart->lessThan($minimumStart) || $this->overlapsAnyBlock($slotStart, $slotEnd, $blocks, $timezone)
                    || $maintenanceBlocks->contains(function (MaintenanceBlock $block) use ($slotStart, $slotEnd): bool {
                        return $this->overlaps(
                            $slotStart,
                            $slotEnd,
                            $block->starts_at->toImmutable(),
                            $block->ends_at->toImmutable(),
                        );
                    })) {
                    continue;
                }

                $slots[$slotStart->format('U.u')] = new AvailabilitySlotData($slotStart, $slotEnd, $timezone);
            }
        }

        ksort($slots, SORT_NATURAL);

        return array_values($slots);
    }

    /**
     * @return list<array{string, AvailabilityTimeRange}>
     */
    private function scheduleRangesForDate(Turf $turf, CarbonImmutable $date): array
    {
        $ranges = [];

        foreach ([$date->subDay(), $date] as $ruleDate) {
            $isPreviousDate = $ruleDate->notEqualTo($date);
            $rule = $turf->availabilityRules->first(
                fn ($candidate): bool => $candidate->is_active && $candidate->weekday === $ruleDate->dayOfWeekIso,
            );

            if ($rule === null) {
                continue;
            }

            foreach ($rule->timeRanges as $range) {
                if (! $isPreviousDate || $range->ends_next_day) {
                    $ranges[] = [$ruleDate->format('Y-m-d'), $range];
                }
            }
        }

        return $ranges;
    }

    /**
     * @param  iterable<SlotBlock>  $blocks
     */
    private function overlapsAnyBlock(CarbonImmutable $startsAt, CarbonImmutable $endsAt, iterable $blocks, string $timezone): bool
    {
        foreach ($blocks as $block) {
            if ($block->is_full_day) {
                continue;
            }

            $blockStartsAt = $this->resolveLocalBoundary($block->block_date, (string) $block->starts_at_time, $timezone, false);
            $blockEndsAt = $this->resolveLocalBoundary(
                $block->ends_next_day ? $this->parseLocalDate($block->block_date, $timezone)->addDay()->format('Y-m-d') : $block->block_date,
                (string) $block->ends_at_time,
                $timezone,
                true,
            );

            if ($blockStartsAt !== null && $blockEndsAt !== null && $this->overlaps($startsAt, $endsAt, $blockStartsAt, $blockEndsAt)) {
                return true;
            }
        }

        return false;
    }

    private function overlaps(CarbonImmutable $startsAt, CarbonImmutable $endsAt, CarbonImmutable $otherStartsAt, CarbonImmutable $otherEndsAt): bool
    {
        return $startsAt->lessThan($otherEndsAt) && $endsAt->greaterThan($otherStartsAt);
    }

    private function isWithinBookingWindow(Turf $turf, CarbonImmutable $localDate, CarbonImmutable $now, string $timezone): bool
    {
        $today = $now->setTimezone($timezone)->startOfDay();

        return $localDate->greaterThanOrEqualTo($today)
            && $localDate->lessThanOrEqualTo($today->addDays($turf->advance_booking_window_days));
    }

    private function parseLocalDate(string $date, string $timezone): CarbonImmutable
    {
        $parsed = CarbonImmutable::createFromFormat('!Y-m-d', $date, $timezone);

        if ($parsed === null || $parsed->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException('Availability dates must use the YYYY-MM-DD format.');
        }

        return $parsed;
    }

    private function resolveLocalBoundary(string $date, string $time, string $timezone, bool $isClosingBoundary): ?CarbonImmutable
    {
        $label = "$date $time";
        $wallTime = CarbonImmutable::createFromFormat('!Y-m-d H:i:s', $label, 'UTC');

        if ($wallTime === null || $wallTime->format('Y-m-d H:i:s') !== $label) {
            throw new InvalidArgumentException('Availability times must use the HH:MM:SS format.');
        }

        $zone = new DateTimeZone($timezone);
        $candidates = [];

        foreach ($zone->getTransitions($wallTime->getTimestamp() - 172800, $wallTime->getTimestamp() + 172800) as $transition) {
            $candidate = CarbonImmutable::createFromTimestampUTC($wallTime->getTimestamp() - $transition['offset']);

            if ($candidate->setTimezone($zone)->format('Y-m-d H:i:s') === $label) {
                $candidates[$candidate->getTimestamp()] = $candidate;
            }
        }

        if ($candidates === []) {
            return null;
        }

        ksort($candidates, SORT_NUMERIC);

        return $isClosingBoundary ? array_last($candidates) : array_first($candidates);
    }
}
