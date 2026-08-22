<?php

use App\Domain\Users\Enums\UserStatus;
use App\Domain\Vendors\Enums\VendorMembershipStatus;
use App\Domain\Vendors\Enums\VendorStatus;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorMembership;
use App\Models\VendorStatusHistory;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(DatabaseSeeder::class)->run();
});

test('a reviewer can reject a pending vendor once with an owner-safe reason', function (): void {
    $reviewer = lifecycleAdmin('admin_operations');
    $vendor = lifecycleVendor(VendorStatus::PendingApproval);

    $this->actingAs($reviewer)
        ->post(route('admin.vendor_reviews.reject', $vendor), [
            'submission_version' => $vendor->submission_version,
            'reason_code' => 'document_verification_required',
            'reason_message' => 'Upload a current business registration document before resubmitting.',
        ])
        ->assertRedirect(route('admin.vendor_reviews.index'));

    $this->post(route('admin.vendor_reviews.reject', $vendor), [
        'submission_version' => $vendor->submission_version,
        'reason_code' => 'document_verification_required',
        'reason_message' => 'Upload a current business registration document before resubmitting.',
    ])->assertRedirect(route('admin.vendor_reviews.index'));

    expect($vendor->fresh()->status)->toBe(VendorStatus::Rejected)
        ->and(VendorStatusHistory::query()->where('vendor_id', $vendor->id)->where('to_status', VendorStatus::Rejected->value)->count())->toBe(1);
});

test('only an owner can reopen a rejected vendor registration and the new draft has a new version', function (): void {
    $owner = User::factory()->create(['status' => UserStatus::Active]);
    $vendor = lifecycleVendor(VendorStatus::Rejected);
    VendorMembership::query()->create([
        'vendor_id' => $vendor->id,
        'user_id' => $owner->id,
        'role' => 'vendor_owner',
        'status' => VendorMembershipStatus::Active,
    ]);
    VendorStatusHistory::query()->create([
        'vendor_id' => $vendor->id,
        'sequence' => 1,
        'to_status' => VendorStatus::Rejected->value,
        'reason_code' => 'document_verification_required',
        'reason_message' => 'Upload a current business registration document before resubmitting.',
        'transitioned_at' => now(),
    ]);

    $this->actingAs($owner)
        ->get(route('vendor.onboarding.show'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('vendor.status', VendorStatus::Rejected->value)
            ->where('rejection.reason_message', 'Upload a current business registration document before resubmitting.'),
        );

    $this->post(route('vendor.onboarding.resubmission.prepare', $vendor), ['submission_version' => 1])
        ->assertRedirect(route('vendor.onboarding.show'));

    $this->post(route('vendor.onboarding.resubmission.prepare', $vendor), ['submission_version' => 1])
        ->assertRedirect(route('vendor.onboarding.show'));

    expect($vendor->fresh()->status)->toBe(VendorStatus::Draft)
        ->and($vendor->fresh()->submission_version)->toBe(2)
        ->and(VendorStatusHistory::query()->where('vendor_id', $vendor->id)->where('to_status', VendorStatus::Draft->value)->count())->toBe(1);
});

test('suspension requires its dedicated permission and is idempotent', function (): void {
    $supportUser = lifecycleAdmin('admin_support');
    $operationsUser = lifecycleAdmin('admin_operations');
    $vendor = lifecycleVendor(VendorStatus::Approved);

    $this->actingAs($supportUser)
        ->post(route('admin.vendor_operations.suspend', $vendor), [
            'submission_version' => $vendor->submission_version,
            'reason_code' => 'compliance_review',
            'reason_message' => 'Compliance review is required before new commerce can continue.',
        ])
        ->assertForbidden();

    $this->actingAs($operationsUser)
        ->post(route('admin.vendor_operations.suspend', $vendor), [
            'submission_version' => $vendor->submission_version,
            'reason_code' => 'compliance_review',
            'reason_message' => 'Compliance review is required before new commerce can continue.',
        ])
        ->assertRedirect(route('admin.vendor_operations.index'));

    $this->post(route('admin.vendor_operations.suspend', $vendor), [
        'submission_version' => $vendor->submission_version,
        'reason_code' => 'compliance_review',
        'reason_message' => 'Compliance review is required before new commerce can continue.',
    ])->assertRedirect(route('admin.vendor_operations.index'));

    expect($vendor->fresh()->status)->toBe(VendorStatus::Suspended)
        ->and(VendorStatusHistory::query()->where('vendor_id', $vendor->id)->where('to_status', VendorStatus::Suspended->value)->count())->toBe(1);
});

test('reactivation requires a permission and an active vendor owner', function (): void {
    $supportUser = lifecycleAdmin('admin_support');
    $operationsUser = lifecycleAdmin('admin_operations');
    $vendor = lifecycleVendor(VendorStatus::Suspended);

    $this->actingAs($supportUser)
        ->post(route('admin.vendor_operations.reactivate', $vendor), [
            'submission_version' => $vendor->submission_version,
            'reason_message' => 'Compliance review cleared and the business is eligible to operate.',
        ])
        ->assertForbidden();

    $this->actingAs($operationsUser)
        ->from(route('admin.vendor_operations.show', $vendor))
        ->post(route('admin.vendor_operations.reactivate', $vendor), [
            'submission_version' => $vendor->submission_version,
            'reason_message' => 'Compliance review cleared and the business is eligible to operate.',
        ])
        ->assertRedirect(route('admin.vendor_operations.show', $vendor))
        ->assertSessionHasErrors('vendor');

    $owner = User::factory()->create(['status' => UserStatus::Active]);
    VendorMembership::query()->create([
        'vendor_id' => $vendor->id,
        'user_id' => $owner->id,
        'role' => 'vendor_owner',
        'status' => VendorMembershipStatus::Active,
    ]);

    $this->post(route('admin.vendor_operations.reactivate', $vendor), [
        'submission_version' => $vendor->submission_version,
        'reason_message' => 'Compliance review cleared and the business is eligible to operate.',
    ])->assertRedirect(route('admin.vendor_operations.index'));

    expect($vendor->fresh()->status)->toBe(VendorStatus::Approved)
        ->and(VendorStatusHistory::query()->where('vendor_id', $vendor->id)->where('to_status', VendorStatus::Approved->value)->count())->toBe(1);
});

function lifecycleAdmin(string $roleCode): User
{
    $user = User::factory()->create(['status' => UserStatus::Active]);
    $user->roles()->attach(Role::query()->where('code', $roleCode)->firstOrFail());

    return $user;
}

function lifecycleVendor(VendorStatus $status): Vendor
{
    return Vendor::factory()->create([
        'status' => $status,
        'legal_name' => 'Lifecycle Sports Private Limited',
        'display_name' => 'Lifecycle Arena',
        'submission_version' => 1,
    ]);
}
