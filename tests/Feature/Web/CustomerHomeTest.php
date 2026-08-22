<?php

use App\Domain\Locations\Enums\LocationStatus;
use App\Domain\Pricing\Enums\PricingRuleType;
use App\Domain\Turfs\Enums\TurfStatus;
use App\Models\Location;
use App\Models\Sport;
use App\Models\Turf;
use App\Models\Vendor;
use Inertia\Testing\AssertableInertia as Assert;

test('customer home lists active turfs with customer-safe summaries', function (): void {
    $sport = Sport::query()->create([
        'name' => 'Football',
        'code' => 'football',
        'is_active' => true,
    ]);
    Sport::query()->create([
        'name' => 'Hidden Sport',
        'code' => 'hidden_sport',
        'is_active' => false,
    ]);
    $location = Location::query()->create([
        'vendor_id' => Vendor::factory()->create()->id,
        'name' => 'Home Discovery Arena',
        'address_line_1' => '1 Match Road',
        'city' => 'Bengaluru',
        'state' => 'Karnataka',
        'postal_code' => '560001',
        'country_code' => 'IN',
        'locality' => 'Indiranagar',
        'latitude' => 12.971600,
        'longitude' => 77.594600,
        'timezone' => 'Asia/Kolkata',
        'status' => LocationStatus::Active->value,
    ]);
    $turf = Turf::query()->create([
        'location_id' => $location->id,
        'name' => 'Home Match Turf',
        'status' => TurfStatus::Active->value,
        'booking_lead_time_minutes' => 0,
        'advance_booking_window_days' => 30,
        'default_slot_duration_minutes' => 60,
        'min_booking_duration_minutes' => 60,
        'max_booking_duration_minutes' => 240,
    ]);
    $turf->pricingRules()->create([
        'rule_type' => PricingRuleType::Base,
        'price_minor' => 50000,
        'currency' => 'INR',
        'priority' => 100,
        'is_active' => true,
        'ends_next_day' => false,
    ]);

    $this->get(route('customer.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/Home')
            ->where('nearbyTurfs.0.id', $turf->id)
            ->where('nearbyTurfs.0.name', 'Home Match Turf')
            ->where('nearbyTurfs.0.location.locality', 'Indiranagar')
            ->where('nearbyTurfs.0.pricing_summary.starting_price', '500.00')
            ->where('nearbyTurfs.0.detail_url', route('customer.turfs.show', $turf))
            ->where('sports.0.id', $sport->id)
            ->where('sports.0.name', 'Football')
            ->has('sports', 1),
        );
});
