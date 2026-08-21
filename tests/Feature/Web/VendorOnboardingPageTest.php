<?php

use App\Domain\Users\Enums\UserStatus;
use App\Domain\Vendors\Enums\VendorStatus;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorMembership;
use App\Models\VendorStatusHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('the vendor onboarding page redirects guests to login', function (): void {
    $this->get(route('vendor.onboarding.show'))
        ->assertRedirect(route('login'));
});

test('an authenticated user can start vendor onboarding and create a draft vendor with an owner membership', function (): void {
    $user = User::factory()->create([
        'name' => 'Riya Sharma',
        'mobile_number' => '+919900001111',
        'email' => 'riya@example.test',
        'status' => UserStatus::Active,
    ]);

    $this->actingAs($user)
        ->get(route('vendor.onboarding.show'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('vendor/Onboarding')
            ->where('vendor.status', VendorStatus::Draft->value)
            ->where('vendor.legal_name', null)
            ->where('vendor.display_name', null)
            ->where('vendor.legal_entity_type', null)
            ->where('vendor.submission_version', 1)
            ->where('owner.name', 'Riya Sharma')
            ->where('owner.mobile_number', '+919900001111')
            ->where('owner.email', 'riya@example.test'),
        );

    $vendor = Vendor::query()->sole();
    $membership = VendorMembership::query()->sole();
    $history = VendorStatusHistory::query()->sole();

    expect($vendor->status)->toBe(VendorStatus::Draft)
        ->and($vendor->submission_version)->toBe(1)
        ->and($membership->vendor_id)->toBe($vendor->id)
        ->and($membership->user_id)->toBe($user->id)
        ->and($membership->role)->toBe('vendor_owner')
        ->and($history->vendor_id)->toBe($vendor->id)
        ->and($history->actor_user_id)->toBe($user->id)
        ->and($history->sequence)->toBe(1)
        ->and($history->to_status)->toBe(VendorStatus::Draft->value);
});

test('an authenticated owner reuses the same vendor draft when reopening vendor onboarding', function (): void {
    $user = User::factory()->create([
        'status' => UserStatus::Active,
    ]);
    $vendor = Vendor::factory()->create([
        'submission_version' => 4,
    ]);

    VendorMembership::query()->create([
        'vendor_id' => $vendor->id,
        'user_id' => $user->id,
        'role' => 'vendor_owner',
        'status' => 'active',
    ]);

    VendorStatusHistory::query()->create([
        'vendor_id' => $vendor->id,
        'actor_user_id' => $user->id,
        'sequence' => 1,
        'to_status' => VendorStatus::Draft->value,
        'transitioned_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('vendor.onboarding.show'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('vendor/Onboarding')
            ->where('vendor.id', $vendor->id)
            ->where('vendor.submission_version', 4),
        );

    expect(Vendor::query()->count())->toBe(1)
        ->and(VendorMembership::query()->count())->toBe(1)
        ->and(VendorStatusHistory::query()->count())->toBe(1);
});

test('an active vendor owner can save business details for a draft', function (): void {
    $user = User::factory()->create([
        'status' => UserStatus::Active,
    ]);
    $vendor = Vendor::factory()->create();

    VendorMembership::query()->create([
        'vendor_id' => $vendor->id,
        'user_id' => $user->id,
        'role' => 'vendor_owner',
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->put(route('vendor.onboarding.business-details.update', $vendor), [
            'legal_name' => '  Acme Sports Private Limited  ',
            'display_name' => '  Acme Sports Arena  ',
            'legal_entity_type' => '  private_limited_company  ',
        ])
        ->assertRedirect(route('vendor.onboarding.show'));

    $this->assertDatabaseHas('vendors', [
        'id' => $vendor->id,
        'legal_name' => 'Acme Sports Private Limited',
        'display_name' => 'Acme Sports Arena',
        'legal_entity_type' => 'private_limited_company',
    ]);
});

test('vendor business details require all legal fields', function (): void {
    $user = User::factory()->create([
        'status' => UserStatus::Active,
    ]);
    $vendor = Vendor::factory()->create();

    VendorMembership::query()->create([
        'vendor_id' => $vendor->id,
        'user_id' => $user->id,
        'role' => 'vendor_owner',
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->from(route('vendor.onboarding.show'))
        ->put(route('vendor.onboarding.business-details.update', $vendor), [])
        ->assertRedirect(route('vendor.onboarding.show'))
        ->assertSessionHasErrors([
            'legal_name',
            'display_name',
            'legal_entity_type',
        ]);
});

test('a vendor owner cannot update another vendor business details', function (): void {
    $user = User::factory()->create([
        'status' => UserStatus::Active,
    ]);
    $vendor = Vendor::factory()->create();

    $this->actingAs($user)
        ->put(route('vendor.onboarding.business-details.update', $vendor), [
            'legal_name' => 'Acme Sports Private Limited',
            'display_name' => 'Acme Sports Arena',
            'legal_entity_type' => 'private_limited_company',
        ])
        ->assertForbidden();
});

test('vendor business details cannot be edited after the draft state', function (): void {
    $user = User::factory()->create([
        'status' => UserStatus::Active,
    ]);
    $vendor = Vendor::factory()->approved()->create();

    VendorMembership::query()->create([
        'vendor_id' => $vendor->id,
        'user_id' => $user->id,
        'role' => 'vendor_owner',
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->from(route('vendor.onboarding.show'))
        ->put(route('vendor.onboarding.business-details.update', $vendor), [
            'legal_name' => 'Acme Sports Private Limited',
            'display_name' => 'Acme Sports Arena',
            'legal_entity_type' => 'private_limited_company',
        ])
        ->assertRedirect(route('vendor.onboarding.show'))
        ->assertSessionHasErrors('vendor');

    expect($vendor->fresh()->legal_name)->toBeNull();
});
