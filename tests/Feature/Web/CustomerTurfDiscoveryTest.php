<?php

use App\Domain\Locations\Enums\LocationStatus;
use App\Domain\Pricing\Enums\PricingRuleType;
use App\Domain\Turfs\Enums\TurfStatus;
use App\Models\Amenity;
use App\Models\AvailabilityRule;
use App\Models\Location;
use App\Models\Sport;
use App\Models\Turf;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('customers can search turfs with place sport amenity price distance availability and pagination filters', function (): void {
    $football = Sport::query()->create([
        'name' => 'Football',
        'code' => 'football',
        'is_active' => true,
    ]);
    $badminton = Sport::query()->create([
        'name' => 'Badminton',
        'code' => 'badminton',
        'is_active' => true,
    ]);
    $parking = Amenity::query()->create([
        'name' => 'Parking',
        'code' => 'parking',
        'is_active' => true,
    ]);
    $shower = Amenity::query()->create([
        'name' => 'Showers',
        'code' => 'showers',
        'is_active' => true,
    ]);

    $matchingTurf = searchableTurf([
        'location_name' => 'Indiranagar Arena',
        'location_city' => 'Bengaluru',
        'location_locality' => 'Indiranagar',
        'latitude' => 12.971600,
        'longitude' => 77.594600,
        'name' => 'Football Arena',
        'is_indoor' => false,
    ]);
    $matchingTurf->sports()->sync([$football->id]);
    $matchingTurf->location->amenities()->sync([$parking->id]);
    $matchingTurf->amenities()->sync([$shower->id]);
    $matchingTurf->pricingRules()->create(pricingRuleAttributes(10000));
    $matchingTurf->pricingRules()->create(pricingRuleAttributes(14000, 200));
    addAvailabilityWindow($matchingTurf, 1, '09:00:00', '12:00:00');

    $otherTurf = searchableTurf([
        'location_name' => 'HSR Indoor Courts',
        'location_city' => 'Bengaluru',
        'location_locality' => 'HSR Layout',
        'latitude' => 12.913900,
        'longitude' => 77.638700,
        'name' => 'Badminton Pod',
        'is_indoor' => true,
    ]);
    $otherTurf->sports()->sync([$badminton->id]);
    $otherTurf->pricingRules()->create(pricingRuleAttributes(22000));
    addAvailabilityWindow($otherTurf, 1, '07:00:00', '08:00:00');

    searchableTurf([
        'location_name' => 'Dormant Venue',
        'location_city' => 'Bengaluru',
        'location_locality' => 'Whitefield',
        'latitude' => 12.969000,
        'longitude' => 77.750000,
        'name' => 'Hidden Turf',
        'turf_status' => TurfStatus::Inactive->value,
    ]);

    $this->get(route('customer.search.index', [
        'latitude' => '12.971599',
        'longitude' => '77.594566',
        'city' => 'Bengaluru',
        'locality' => 'Indiranagar',
        'turf_name' => 'Football',
        'sport_ids' => [$football->id],
        'amenity_ids' => [$parking->id, $shower->id],
        'max_price' => '120',
        'distance_meters' => 5000,
        'date' => '2026-08-24',
        'sort' => 'distance',
        'per_page' => 1,
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/Search')
            ->where('results.meta.total', 1)
            ->where('results.meta.per_page', 1)
            ->where('results.data.0.id', $matchingTurf->id)
            ->where('results.data.0.location.city', 'Bengaluru')
            ->where('results.data.0.pricing_summary.starting_price', '100.00')
            ->where('results.data.0.availability_summary.has_availability', true)
            ->where('results.data.0.availability_summary.available_slots_count', 3)
            ->where('results.data.0.sports.0.name', 'Football')
            ->where('results.data.0.amenities.0.name', 'Parking'),
        );
});

test('customers can open a turf detail page with location catalog pricing and availability summaries', function (): void {
    $football = Sport::query()->create([
        'name' => 'Football',
        'code' => 'football_detail',
        'is_active' => true,
    ]);
    $parking = Amenity::query()->create([
        'name' => 'Parking',
        'code' => 'parking_detail',
        'is_active' => true,
    ]);

    $turf = searchableTurf([
        'location_name' => 'Koramangala Matchpoint',
        'location_city' => 'Bengaluru',
        'location_locality' => 'Koramangala',
        'latitude' => 12.935200,
        'longitude' => 77.624500,
        'name' => 'Match Turf',
        'description' => 'Play under evening lights.',
        'is_indoor' => true,
        'capacity_count' => 12,
        'surface_type' => 'artificial_grass',
    ]);
    $turf->sports()->sync([$football->id]);
    $turf->location->amenities()->sync([$parking->id]);
    $turf->pricingRules()->create(pricingRuleAttributes(18000));
    addAvailabilityWindow($turf, 1, '18:00:00', '20:00:00');

    $this->get(route('customer.turfs.show', [
        'turf' => $turf,
        'date' => '2026-08-24',
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/TurfDetails')
            ->where('turf.id', $turf->id)
            ->where('turf.location.name', 'Koramangala Matchpoint')
            ->where('turf.sports.0.name', 'Football')
            ->where('turf.amenities.0.name', 'Parking')
            ->where('turf.pricing_summary.starting_price', '180.00')
            ->where('turf.availability_summary.has_availability', true)
            ->where('turf.availability_summary.available_slots_count', 2)
            ->where('turf.availability_summary.sample_slots.0.starts_at_time', '18:00'),
        );
});

test('customer turf search validates distance sorting requirements and price bounds', function (): void {
    $this->from(route('customer.search.index'))
        ->get(route('customer.search.index', [
            'sort' => 'distance',
            'min_price' => '150',
            'max_price' => '100',
        ]))
        ->assertRedirect(route('customer.search.index'))
        ->assertSessionHasErrors(['sort', 'max_price']);
});

/**
 * @param  array<string, mixed>  $overrides
 */
function searchableTurf(array $overrides = []): Turf
{
    $vendor = Vendor::factory()->create();
    $location = Location::query()->create([
        'vendor_id' => $vendor->id,
        'name' => $overrides['location_name'] ?? 'Discovery Venue',
        'address_line_1' => '1 Discovery Road',
        'city' => $overrides['location_city'] ?? 'Bengaluru',
        'state' => 'Karnataka',
        'postal_code' => '560001',
        'country_code' => 'IN',
        'locality' => $overrides['location_locality'] ?? null,
        'latitude' => $overrides['latitude'] ?? 12.971600,
        'longitude' => $overrides['longitude'] ?? 77.594600,
        'timezone' => 'Asia/Kolkata',
        'status' => $overrides['location_status'] ?? LocationStatus::Active->value,
    ]);

    return Turf::query()->create([
        'location_id' => $location->id,
        'name' => $overrides['name'] ?? 'Discovery Turf',
        'description' => $overrides['description'] ?? null,
        'status' => $overrides['turf_status'] ?? TurfStatus::Active->value,
        'surface_type' => $overrides['surface_type'] ?? null,
        'is_indoor' => $overrides['is_indoor'] ?? null,
        'capacity_count' => $overrides['capacity_count'] ?? null,
        'booking_lead_time_minutes' => 0,
        'advance_booking_window_days' => 30,
        'default_slot_duration_minutes' => 60,
        'min_booking_duration_minutes' => 60,
        'max_booking_duration_minutes' => 240,
    ]);
}

function addAvailabilityWindow(Turf $turf, int $weekday, string $startsAtTime, string $endsAtTime): void
{
    $rule = AvailabilityRule::query()->create([
        'turf_id' => $turf->id,
        'weekday' => $weekday,
        'is_active' => true,
    ]);

    $rule->timeRanges()->create([
        'sequence' => 1,
        'starts_at_time' => $startsAtTime,
        'ends_at_time' => $endsAtTime,
        'ends_next_day' => false,
    ]);
}

/**
 * @return array<string, mixed>
 */
function pricingRuleAttributes(int $priceMinor, int $priority = 100): array
{
    return [
        'rule_type' => PricingRuleType::Base,
        'price_minor' => $priceMinor,
        'currency' => 'INR',
        'priority' => $priority,
        'effective_from_date' => null,
        'effective_until_date' => null,
        'weekday' => null,
        'special_date' => null,
        'starts_at_time' => null,
        'ends_at_time' => null,
        'ends_next_day' => false,
        'is_active' => true,
    ];
}
