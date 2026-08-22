<?php

namespace App\Domain\Availability\Repositories;

use App\Models\MaintenanceBlock;
use App\Models\SlotBlock;
use App\Models\Turf;
use Carbon\CarbonImmutable;

final class AvailabilityRepository
{
    /**
     * @param  list<array{weekday: int, is_active: bool, time_ranges: list<array{starts_at_time: string, ends_at_time: string, ends_next_day: bool}>}>  $schedule
     */
    public function syncWeeklySchedule(Turf $turf, array $schedule): void
    {
        $turf->availabilityRules()->delete();

        usort($schedule, fn (array $left, array $right): int => $left['weekday'] <=> $right['weekday']);

        foreach ($schedule as $rulePayload) {
            usort(
                $rulePayload['time_ranges'],
                fn (array $left, array $right): int => $left['starts_at_time'] <=> $right['starts_at_time'],
            );

            $rule = $turf->availabilityRules()->create([
                'weekday' => $rulePayload['weekday'],
                'is_active' => $rulePayload['is_active'],
            ]);

            foreach ($rulePayload['time_ranges'] as $sequence => $range) {
                $rule->timeRanges()->create([
                    'sequence' => $sequence + 1,
                    'starts_at_time' => $range['starts_at_time'],
                    'ends_at_time' => $range['ends_at_time'],
                    'ends_next_day' => $range['ends_next_day'],
                ]);
            }
        }
    }

    /** @param array<string, int> $attributes */
    public function updateConfiguration(Turf $turf, array $attributes): void
    {
        $turf->update($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function createSlotBlock(Turf $turf, array $attributes): SlotBlock
    {
        return $turf->slotBlocks()->create($attributes);
    }

    public function deleteSlotBlock(SlotBlock $block): void
    {
        $block->delete();
    }

    public function createMaintenanceBlock(Turf $turf, CarbonImmutable $startsAt, CarbonImmutable $endsAt, ?string $reason): MaintenanceBlock
    {
        return $turf->maintenanceBlocks()->create([
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'reason' => $reason,
        ]);
    }

    public function deleteMaintenanceBlock(MaintenanceBlock $block): void
    {
        $block->delete();
    }
}
