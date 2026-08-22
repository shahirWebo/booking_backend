<?php

namespace App\Domain\Availability\Actions;

use App\Domain\Availability\Repositories\AvailabilityRepository;
use App\Models\MaintenanceBlock;
use App\Models\SlotBlock;
use App\Models\Turf;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ManageTurfAvailabilityAction
{
    public function __construct(private readonly AvailabilityRepository $availability) {}

    /** @param array<string, int> $attributes */
    public function updateConfiguration(Turf $turf, array $attributes): void
    {
        $this->availability->updateConfiguration($turf, $attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function createSlotBlock(Turf $turf, array $attributes): SlotBlock
    {
        return $this->availability->createSlotBlock($turf, $attributes);
    }

    public function deleteSlotBlock(Turf $turf, SlotBlock $block): void
    {
        if ($block->turf_id !== $turf->id) {
            throw new InvalidArgumentException('The slot block does not belong to this turf.');
        }

        $this->availability->deleteSlotBlock($block);
    }

    public function createMaintenanceBlock(Turf $turf, CarbonImmutable $startsAt, CarbonImmutable $endsAt, ?string $reason): MaintenanceBlock
    {
        return $this->availability->createMaintenanceBlock($turf, $startsAt, $endsAt, $reason);
    }

    public function deleteMaintenanceBlock(Turf $turf, MaintenanceBlock $block): void
    {
        if ($block->turf_id !== $turf->id) {
            throw new InvalidArgumentException('The maintenance block does not belong to this turf.');
        }

        $this->availability->deleteMaintenanceBlock($block);
    }

    public function copySchedule(Turf $source, Turf $target): void
    {
        $source->loadMissing(['location', 'availabilityRules.timeRanges']);
        $target->loadMissing('location');

        if ($source->location->vendor_id !== $target->location->vendor_id || $source->id === $target->id) {
            throw new InvalidArgumentException('Schedules can only be copied between different turfs owned by the same vendor.');
        }

        $schedule = [];

        foreach ($source->availabilityRules as $rule) {
            $ranges = [];

            foreach ($rule->timeRanges as $range) {
                $ranges[] = [
                    'starts_at_time' => $range->starts_at_time,
                    'ends_at_time' => $range->ends_at_time,
                    'ends_next_day' => $range->ends_next_day,
                ];
            }

            $schedule[] = [
                'weekday' => $rule->weekday,
                'is_active' => $rule->is_active,
                'time_ranges' => $ranges,
            ];
        }

        DB::transaction(fn () => $this->availability->syncWeeklySchedule($target, $schedule));
    }
}
