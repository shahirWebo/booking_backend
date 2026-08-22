<?php

use App\Domain\Pricing\Services\PricingService;
use App\Models\Location;
use App\Models\PricingRule;
use App\Models\Turf;
use App\Models\Vendor;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the most specific rule wins when matching rules have the same priority', function (): void {
    $turf = pricingTestTurf();
    $base = pricingRule($turf, ['rule_type' => 'base', 'price_minor' => 10000, 'priority' => 100]);
    $weekend = pricingRule($turf, ['rule_type' => 'weekend', 'price_minor' => 12000, 'priority' => 100]);
    $peak = pricingRule($turf, [
        'rule_type' => 'peak_hour',
        'price_minor' => 15000,
        'priority' => 100,
        'starts_at_time' => '18:00:00',
        'ends_at_time' => '21:00:00',
    ]);
    $special = pricingRule($turf, [
        'rule_type' => 'special_date',
        'price_minor' => 20000,
        'priority' => 100,
        'special_date' => '2026-08-23',
    ]);

    $quote = app(PricingService::class)->quote($turf, [
        pricingInterval('2026-08-23T13:30:00Z', '2026-08-23T14:30:00Z'), // 19:00 Asia/Kolkata.
    ]);

    expect($quote->totalMinor)->toBe(20000)
        ->and($quote->currency)->toBe('INR')
        ->and($quote->slots)->toHaveCount(1)
        ->and($quote->slots[0]->pricingRuleId)->toBe($special->id)
        ->and($base->id)->not->toBe($weekend->id)
        ->and($peak->id)->not->toBe($special->id);
});

test('lower numeric priority wins and long selections are priced as default slot units', function (): void {
    $turf = pricingTestTurf([
        'default_slot_duration_minutes' => 30,
        'min_booking_duration_minutes' => 30,
    ]);
    pricingRule($turf, ['rule_type' => 'base', 'price_minor' => 5000, 'priority' => 100]);
    $weekday = pricingRule($turf, [
        'rule_type' => 'weekday',
        'price_minor' => 6500,
        'priority' => 10,
        'weekday' => 1,
    ]);

    $quote = app(PricingService::class)->quote($turf, [
        pricingInterval('2026-08-24T03:30:00Z', '2026-08-24T05:00:00Z'), // Monday 09:00 to 10:30 Asia/Kolkata.
    ]);

    expect($quote->totalMinor)->toBe(19500)
        ->and($quote->slots)->toHaveCount(3)
        ->and(array_map(fn ($slot): int => $slot->pricingRuleId, $quote->slots))->toBe([$weekday->id, $weekday->id, $weekday->id]);
});

test('overnight peak pricing uses the preceding local date for its effective range', function (): void {
    $turf = pricingTestTurf();
    pricingRule($turf, ['rule_type' => 'base', 'price_minor' => 10000]);
    $peak = pricingRule($turf, [
        'rule_type' => 'peak_hour',
        'price_minor' => 18000,
        'priority' => 10,
        'effective_from_date' => '2026-08-28',
        'effective_until_date' => '2026-08-28',
        'starts_at_time' => '22:00:00',
        'ends_at_time' => '02:00:00',
        'ends_next_day' => true,
    ]);

    $quote = app(PricingService::class)->quote($turf, [
        pricingInterval('2026-08-28T19:30:00Z', '2026-08-28T20:30:00Z'), // Saturday 01:00 Asia/Kolkata.
    ]);

    expect($quote->totalMinor)->toBe(18000)
        ->and($quote->slots[0]->pricingRuleId)->toBe($peak->id);
});

test('pricing rejects intervals that cannot be divided into the configured slot duration', function (): void {
    $turf = pricingTestTurf();
    pricingRule($turf, ['rule_type' => 'base', 'price_minor' => 10000]);

    expect(fn () => app(PricingService::class)->quote($turf, [
        pricingInterval('2026-08-24T03:30:00Z', '2026-08-24T04:15:00Z'),
    ]))->toThrow(InvalidArgumentException::class, 'multiple of the turf slot duration');
});

test('pricing rejects a quote when no active rule applies', function (): void {
    $turf = pricingTestTurf();

    expect(fn () => app(PricingService::class)->quote($turf, [
        pricingInterval('2026-08-24T03:30:00Z', '2026-08-24T04:30:00Z'),
    ]))->toThrow(DomainException::class, 'No active pricing rule');
});

/** @param array<string, mixed> $overrides */
function pricingTestTurf(array $overrides = []): Turf
{
    $vendor = Vendor::factory()->create();
    $location = Location::query()->create([
        'vendor_id' => $vendor->id,
        'name' => 'Pricing Test Location',
        'address_line_1' => '1 Test Street',
        'city' => 'Bengaluru',
        'state' => 'Karnataka',
        'postal_code' => '560001',
        'country_code' => 'IN',
        'timezone' => 'Asia/Kolkata',
        'status' => 'active',
    ]);

    return Turf::query()->create([
        'location_id' => $location->id,
        'name' => 'Pricing Test Turf',
        'status' => 'active',
        'booking_lead_time_minutes' => 0,
        'advance_booking_window_days' => 30,
        'default_slot_duration_minutes' => 60,
        'min_booking_duration_minutes' => 60,
        'max_booking_duration_minutes' => 240,
        ...$overrides,
    ]);
}

/** @param array<string, mixed> $overrides */
function pricingRule(Turf $turf, array $overrides = []): PricingRule
{
    return $turf->pricingRules()->create([
        'rule_type' => 'base',
        'price_minor' => 10000,
        'currency' => 'INR',
        'priority' => 100,
        'ends_next_day' => false,
        'is_active' => true,
        ...$overrides,
    ]);
}

/** @return array{starts_at: CarbonImmutable, ends_at: CarbonImmutable} */
function pricingInterval(string $startsAt, string $endsAt): array
{
    return [
        'starts_at' => CarbonImmutable::parse($startsAt),
        'ends_at' => CarbonImmutable::parse($endsAt),
    ];
}
