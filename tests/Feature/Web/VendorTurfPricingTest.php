<?php

use App\Domain\Users\Enums\UserStatus;
use App\Models\Location;
use App\Models\PricingRule;
use App\Models\Turf;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a vendor manager can create update list and delete a turf pricing rule', function (): void {
    [$user, $turf] = pricingManagerAndTurf();
    $payload = vendorPricingRulePayload();

    $this->actingAs($user)
        ->from(route('vendor.turfs.availability', $turf))
        ->post(route('vendor.turfs.pricing-rules.store', $turf), $payload)
        ->assertRedirect(route('vendor.turfs.availability', $turf));

    $rule = PricingRule::query()->sole();

    $this->actingAs($user)
        ->getJson(route('vendor.turfs.pricing-rules.index', $turf))
        ->assertOk()
        ->assertJsonPath('pricing_rules.0.id', $rule->id)
        ->assertJsonPath('pricing_rules.0.currency', 'INR');

    $this->actingAs($user)
        ->from(route('vendor.turfs.availability', $turf))
        ->put(route('vendor.turfs.pricing-rules.update', [$turf, $rule]), [
            ...$payload,
            'price_minor' => 12500,
            'priority' => 5,
        ])
        ->assertRedirect(route('vendor.turfs.availability', $turf));

    $this->assertDatabaseHas('pricing_rules', [
        'id' => $rule->id,
        'price_minor' => 12500,
        'priority' => 5,
    ]);

    $this->actingAs($user)
        ->from(route('vendor.turfs.availability', $turf))
        ->delete(route('vendor.turfs.pricing-rules.destroy', [$turf, $rule]))
        ->assertRedirect(route('vendor.turfs.availability', $turf));

    $this->assertDatabaseMissing('pricing_rules', ['id' => $rule->id]);
});

test('pricing rule requests validate their selector and peak-hour window', function (): void {
    [$user, $turf] = pricingManagerAndTurf();

    $this->actingAs($user)
        ->from(route('vendor.turfs.availability', $turf))
        ->post(route('vendor.turfs.pricing-rules.store', $turf), [
            ...vendorPricingRulePayload(),
            'rule_type' => 'weekday',
            'weekday' => null,
            'starts_at_time' => '18:00',
            'ends_at_time' => '17:00',
        ])
        ->assertRedirect(route('vendor.turfs.availability', $turf))
        ->assertSessionHasErrors(['rule_type', 'starts_at_time']);

    expect(PricingRule::query()->exists())->toBeFalse();
});

test('a vendor cannot change a pricing rule belonging to another turf', function (): void {
    [$user, $turf] = pricingManagerAndTurf();
    [, $otherTurf] = pricingManagerAndTurf();
    $otherRule = $otherTurf->pricingRules()->create([
        ...vendorPricingRulePayload(),
        'currency' => 'INR',
    ]);

    $this->actingAs($user)
        ->put(route('vendor.turfs.pricing-rules.update', [$turf, $otherRule]), vendorPricingRulePayload())
        ->assertForbidden();
});

test('the browser pricing quote endpoint returns a server calculated multi-slot total', function (): void {
    [$user, $turf] = pricingManagerAndTurf();
    $turf->pricingRules()->create([
        ...vendorPricingRulePayload(),
        'currency' => 'INR',
    ]);

    $this->actingAs($user)
        ->postJson(route('vendor.turfs.pricing-rules.quote', $turf), [
            'slots' => [[
                'starts_at' => '2026-08-24T03:30:00.000000Z',
                'ends_at' => '2026-08-24T05:30:00.000000Z',
            ]],
        ])
        ->assertOk()
        ->assertJsonPath('location_timezone', 'Asia/Kolkata')
        ->assertJsonPath('quote.total_minor', 20000)
        ->assertJsonPath('quote.currency', 'INR')
        ->assertJsonCount(2, 'quote.slots');
});

test('the browser pricing quote endpoint rejects durations outside the turf slot grid', function (): void {
    [$user, $turf] = pricingManagerAndTurf();
    $turf->pricingRules()->create([
        ...vendorPricingRulePayload(),
        'currency' => 'INR',
    ]);

    $this->actingAs($user)
        ->postJson(route('vendor.turfs.pricing-rules.quote', $turf), [
            'slots' => [[
                'starts_at' => '2026-08-24T03:30:00.000000Z',
                'ends_at' => '2026-08-24T04:15:00.000000Z',
            ]],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('slots');
});

/** @return array{0: User, 1: Turf} */
function pricingManagerAndTurf(): array
{
    $user = User::factory()->create(['status' => UserStatus::Active]);
    $vendor = Vendor::factory()->create();
    $location = Location::query()->create([
        'vendor_id' => $vendor->id,
        'name' => 'Pricing Manager Location',
        'address_line_1' => '1 Test Street',
        'city' => 'Bengaluru',
        'state' => 'Karnataka',
        'postal_code' => '560001',
        'country_code' => 'IN',
        'timezone' => 'Asia/Kolkata',
        'status' => 'active',
    ]);
    $turf = Turf::query()->create([
        'location_id' => $location->id,
        'name' => 'Pricing Manager Turf',
        'status' => 'active',
        'booking_lead_time_minutes' => 0,
        'advance_booking_window_days' => 30,
        'default_slot_duration_minutes' => 60,
        'min_booking_duration_minutes' => 60,
        'max_booking_duration_minutes' => 240,
    ]);

    VendorMembership::query()->create([
        'vendor_id' => $vendor->id,
        'user_id' => $user->id,
        'role' => 'vendor_manager',
        'status' => 'active',
    ]);

    return [$user, $turf];
}

/** @return array<string, mixed> */
function vendorPricingRulePayload(): array
{
    return [
        'rule_type' => 'base',
        'price_minor' => 10000,
        'currency' => 'inr',
        'priority' => 100,
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
