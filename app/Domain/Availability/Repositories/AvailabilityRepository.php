<?php

namespace App\Domain\Availability\Repositories;

use App\Models\Turf;

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
}
