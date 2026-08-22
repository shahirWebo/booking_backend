<?php

namespace App\Domain\Availability\Actions;

use App\Domain\Availability\Repositories\AvailabilityRepository;
use App\Models\Turf;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class SyncTurfAvailabilityScheduleAction
{
    public function __construct(
        private readonly AvailabilityRepository $availability,
    ) {}

    /**
     * @param  list<array{weekday: int, is_active: bool, time_ranges: list<array{starts_at_time: string, ends_at_time: string, ends_next_day: bool}>}>  $schedule
     */
    public function execute(Turf $turf, array $schedule): Turf
    {
        $this->assertValidSchedule($schedule);

        DB::transaction(function () use ($turf, $schedule): void {
            $this->availability->syncWeeklySchedule($turf, $schedule);
        });

        return $turf->refresh()->load('availabilityRules.timeRanges');
    }

    /**
     * @param  list<array{weekday: int, is_active: bool, time_ranges: list<array{starts_at_time: string, ends_at_time: string, ends_next_day: bool}>}>  $schedule
     */
    private function assertValidSchedule(array $schedule): void
    {
        $weekdays = [];

        foreach ($schedule as $rule) {
            $weekday = $rule['weekday'];

            if ($weekday < 1 || $weekday > 7 || isset($weekdays[$weekday])) {
                throw new InvalidArgumentException('Each weekday must appear at most once in an availability schedule.');
            }

            $weekdays[$weekday] = true;

            if ($rule['is_active'] && $rule['time_ranges'] === []) {
                throw new InvalidArgumentException('An active weekday requires at least one availability time range.');
            }

            $this->assertNonOverlappingRanges($rule['time_ranges']);
        }
    }

    /**
     * @param  list<array{starts_at_time: string, ends_at_time: string, ends_next_day: bool}>  $ranges
     */
    private function assertNonOverlappingRanges(array $ranges): void
    {
        $normalized = [];

        foreach ($ranges as $range) {
            $start = $this->minutesSinceMidnight($range['starts_at_time']);
            $end = $this->minutesSinceMidnight($range['ends_at_time']);
            $endsNextDay = $range['ends_next_day'];

            if ((! $endsNextDay && $end <= $start) || ($endsNextDay && $end >= $start)) {
                throw new InvalidArgumentException('Availability time ranges must have a valid end time.');
            }

            $normalized[] = [
                'start' => $start,
                'end' => $endsNextDay ? $end + 1440 : $end,
            ];
        }

        usort($normalized, fn (array $left, array $right): int => $left['start'] <=> $right['start']);

        foreach ($normalized as $index => $range) {
            if ($index > 0 && $range['start'] < $normalized[$index - 1]['end']) {
                throw new InvalidArgumentException('Availability time ranges cannot overlap on the same weekday.');
            }
        }
    }

    private function minutesSinceMidnight(string $time): int
    {
        $parts = explode(':', $time);

        return ((int) $parts[0] * 60) + (int) $parts[1];
    }
}
