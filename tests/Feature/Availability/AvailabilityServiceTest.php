<?php

use App\Domain\Availability\Actions\SyncTurfAvailabilityScheduleAction;
use App\Domain\Availability\Data\AvailabilitySlotData;
use App\Domain\Availability\Services\AvailabilityService;
use App\Models\Location;
use App\Models\Turf;
use App\Models\Vendor;
use Carbon\CarbonImmutable;

test('weekly schedules persist multiple ordered ranges and compile slots using the configured duration', function (): void {
    $turf = availabilityTestTurf();

    app(SyncTurfAvailabilityScheduleAction::class)->execute($turf, [
        [
            'weekday' => 1,
            'is_active' => true,
            'time_ranges' => [
                ['starts_at_time' => '14:00:00', 'ends_at_time' => '16:00:00', 'ends_next_day' => false],
                ['starts_at_time' => '09:00:00', 'ends_at_time' => '12:00:00', 'ends_next_day' => false],
            ],
        ],
    ]);

    $slots = app(AvailabilityService::class)->slotsForDate(
        $turf,
        '2026-08-24',
        CarbonImmutable::parse('2026-08-22 00:00:00 UTC'),
    );

    expect($turf->availabilityRules)->toHaveCount(1)
        ->and($turf->availabilityRules->first()->timeRanges->pluck('starts_at_time')->all())
        ->toBe(['09:00:00', '14:00:00'])
        ->and(availabilitySlotTimes($slots))
        ->toBe(['09:00:00', '10:00:00', '11:00:00', '14:00:00', '15:00:00']);
});

test('slot and maintenance blocks exclude only overlapping half-open intervals while holidays and full-day blocks close the date', function (): void {
    $turf = availabilityTestTurf();
    availabilitySchedule($turf, 1, '09:00:00', '12:00:00');

    $turf->slotBlocks()->create([
        'block_date' => '2026-08-24',
        'is_full_day' => false,
        'starts_at_time' => '10:00:00',
        'ends_at_time' => '11:00:00',
        'ends_next_day' => false,
    ]);
    $turf->maintenanceBlocks()->create([
        'starts_at' => CarbonImmutable::parse('2026-08-24 05:00:00 UTC'),
        'ends_at' => CarbonImmutable::parse('2026-08-24 05:30:00 UTC'),
        'reason' => 'Lighting inspection',
    ]);

    $service = app(AvailabilityService::class);
    $now = CarbonImmutable::parse('2026-08-22 00:00:00 UTC');

    expect(availabilitySlotTimes($service->slotsForDate($turf, '2026-08-24', $now)))
        ->toBe(['09:00:00', '11:00:00']);

    $turf->slotBlocks()->create([
        'block_date' => '2026-08-31',
        'is_full_day' => true,
        'ends_next_day' => false,
    ]);
    $turf->location->holidays()->create([
        'holiday_date' => '2026-09-07',
        'name' => 'Local holiday',
        'is_closed' => true,
    ]);

    expect($service->slotsForDate($turf, '2026-08-31', $now))->toBe([])
        ->and($service->slotsForDate($turf, '2026-09-07', $now))->toBe([]);
});

test('lead time, advance window, and overnight rules are evaluated in the location timezone', function (): void {
    $turf = availabilityTestTurf(attributes: [
        'booking_lead_time_minutes' => 90,
        'advance_booking_window_days' => 5,
    ]);
    availabilitySchedule($turf, 1, '09:00:00', '12:00:00');
    availabilitySchedule($turf, 5, '22:00:00', '02:00:00', true);
    availabilitySchedule($turf, 7, '09:00:00', '10:00:00');

    $service = app(AvailabilityService::class);
    $now = CarbonImmutable::parse('2026-08-24 03:00:00 UTC'); // 08:30 in Asia/Kolkata.

    expect(availabilitySlotTimes($service->slotsForDate($turf, '2026-08-24', $now)))
        ->toBe(['10:00:00', '11:00:00'])
        ->and($service->slotsForDate($turf, '2026-08-30', $now))->toBe([])
        ->and(availabilitySlotTimes($service->slotsForDate($turf, '2026-08-29', $now)))
        ->toBe(['00:00:00', '01:00:00']);
});

test('DST gaps do not silently shift boundaries and repeated local clock labels retain distinct UTC slots', function (): void {
    $springTurf = availabilityTestTurf('America/New_York');
    availabilitySchedule($springTurf, 7, '02:00:00', '04:00:00');

    $service = app(AvailabilityService::class);
    $now = CarbonImmutable::parse('2026-03-01 00:00:00 UTC');

    expect($service->slotsForDate($springTurf, '2026-03-08', $now))->toBe([]);

    $fallTurf = availabilityTestTurf('America/New_York');
    availabilitySchedule($fallTurf, 7, '00:00:00', '03:00:00');

    $slots = $service->slotsForDate($fallTurf, '2026-11-01', CarbonImmutable::parse('2026-10-25 00:00:00 UTC'));

    expect(availabilitySlotTimes($slots))->toBe(['00:00:00', '01:00:00', '01:00:00', '02:00:00'])
        ->and(array_map(fn (AvailabilitySlotData $slot): string => $slot->startsAt->utc()->format('Y-m-d\\TH:i:s\\Z'), $slots))
        ->toBe([
            '2026-11-01T04:00:00Z',
            '2026-11-01T05:00:00Z',
            '2026-11-01T06:00:00Z',
            '2026-11-01T07:00:00Z',
        ]);
});

/**
 * @param  array<string, int>  $attributes
 */
function availabilityTestTurf(string $timezone = 'Asia/Kolkata', array $attributes = []): Turf
{
    $vendor = Vendor::factory()->create();
    $location = Location::query()->create([
        'vendor_id' => $vendor->id,
        'name' => 'Availability Test Location',
        'address_line_1' => '1 Test Street',
        'city' => 'Bengaluru',
        'state' => 'Karnataka',
        'postal_code' => '560001',
        'country_code' => 'IN',
        'timezone' => $timezone,
        'status' => 'active',
    ]);

    return Turf::query()->create([
        'location_id' => $location->id,
        'name' => 'Availability Test Turf',
        'status' => 'active',
        ...$attributes,
    ]);
}

function availabilitySchedule(Turf $turf, int $weekday, string $startsAtTime, string $endsAtTime, bool $endsNextDay = false): void
{
    $schedule = $turf->availabilityRules()
        ->with('timeRanges')
        ->get()
        ->map(fn ($rule): array => [
            'weekday' => $rule->weekday,
            'is_active' => $rule->is_active,
            'time_ranges' => $rule->timeRanges->map(fn ($range): array => [
                'starts_at_time' => $range->starts_at_time,
                'ends_at_time' => $range->ends_at_time,
                'ends_next_day' => $range->ends_next_day,
            ])->all(),
        ])
        ->reject(fn (array $rule): bool => $rule['weekday'] === $weekday)
        ->values()
        ->all();

    $schedule[] = [
        'weekday' => $weekday,
        'is_active' => true,
        'time_ranges' => [[
            'starts_at_time' => $startsAtTime,
            'ends_at_time' => $endsAtTime,
            'ends_next_day' => $endsNextDay,
        ]],
    ];

    app(SyncTurfAvailabilityScheduleAction::class)->execute($turf, $schedule);
}

/**
 * @param  list<AvailabilitySlotData>  $slots
 * @return list<string>
 */
function availabilitySlotTimes(array $slots): array
{
    return array_map(
        fn (AvailabilitySlotData $slot): string => $slot->startsAt->setTimezone($slot->locationTimezone)->format('H:i:s'),
        $slots,
    );
}
