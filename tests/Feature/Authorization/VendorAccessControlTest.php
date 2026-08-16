<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RBAC-005: Vendor Access Control
 *
 * Tests that vendor staff can only access resources within their assigned vendor,
 * preventing cross-vendor access and enforcing vendor ownership boundaries.
 */
class VendorAccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /**
     * Test: A vendor staff member can view their own vendor.
     */
    public function test_vendor_member_can_view_own_vendor(): void
    {
        $vendor = Vendor::factory()->create();
        $user = User::factory()->create();
        $vendor->memberships()->create([
            'user_id' => $user->id,
            'role' => 'vendor_staff',
            'status' => 'active',
        ]);

        $this->assertTrue($user->can('view', $vendor));
    }

    /**
     * Test: A vendor staff member cannot view another vendor.
     */
    public function test_vendor_member_cannot_view_other_vendor(): void
    {
        $vendor1 = Vendor::factory()->create();
        $vendor2 = Vendor::factory()->create();
        $user = User::factory()->create();

        $vendor1->memberships()->create([
            'user_id' => $user->id,
            'role' => 'vendor_staff',
            'status' => 'active',
        ]);

        $this->assertFalse($user->can('view', $vendor2));
    }

    /**
     * Test: An inactive member cannot view the vendor.
     */
    public function test_inactive_member_cannot_view_vendor(): void
    {
        $vendor = Vendor::factory()->create();
        $user = User::factory()->create();
        $vendor->memberships()->create([
            'user_id' => $user->id,
            'role' => 'vendor_staff',
            'status' => 'inactive',
        ]);

        $this->assertFalse($user->can('view', $vendor));
    }

    /**
     * Test: A vendor manager can update vendor details.
     */
    public function test_vendor_manager_can_update_vendor(): void
    {
        $vendor = Vendor::factory()->create();
        $user = User::factory()->create();
        $vendor->memberships()->create([
            'user_id' => $user->id,
            'role' => 'vendor_manager',
            'status' => 'active',
        ]);

        $this->assertTrue($user->can('update', $vendor));
    }

    /**
     * Test: A vendor owner can update vendor details.
     */
    public function test_vendor_owner_can_update_vendor(): void
    {
        $vendor = Vendor::factory()->create();
        $user = User::factory()->create();
        $vendor->memberships()->create([
            'user_id' => $user->id,
            'role' => 'vendor_owner',
            'status' => 'active',
        ]);

        $this->assertTrue($user->can('update', $vendor));
    }

    /**
     * Test: A vendor staff member cannot update vendor details.
     */
    public function test_vendor_staff_cannot_update_vendor(): void
    {
        $vendor = Vendor::factory()->create();
        $user = User::factory()->create();
        $vendor->memberships()->create([
            'user_id' => $user->id,
            'role' => 'vendor_staff',
            'status' => 'active',
        ]);

        $this->assertFalse($user->can('update', $vendor));
    }

    /**
     * Test: A vendor accountant cannot update vendor details.
     */
    public function test_vendor_accountant_cannot_update_vendor(): void
    {
        $vendor = Vendor::factory()->create();
        $user = User::factory()->create();
        $vendor->memberships()->create([
            'user_id' => $user->id,
            'role' => 'vendor_accountant',
            'status' => 'active',
        ]);

        $this->assertFalse($user->can('update', $vendor));
    }

    /**
     * Test: A user from another vendor cannot update a vendor.
     */
    public function test_user_from_other_vendor_cannot_update_vendor(): void
    {
        $vendor1 = Vendor::factory()->create();
        $vendor2 = Vendor::factory()->create();
        $user = User::factory()->create();

        $vendor1->memberships()->create([
            'user_id' => $user->id,
            'role' => 'vendor_owner',
            'status' => 'active',
        ]);

        $this->assertFalse($user->can('update', $vendor2));
    }

    /**
     * Test: Only vendor owners can manage staff.
     */
    public function test_vendor_owner_can_manage_staff(): void
    {
        $vendor = Vendor::factory()->create();
        $user = User::factory()->create();
        $vendor->memberships()->create([
            'user_id' => $user->id,
            'role' => 'vendor_owner',
            'status' => 'active',
        ]);

        $this->assertTrue($user->can('manageStaff', $vendor));
    }

    /**
     * Test: Vendor managers cannot manage staff.
     */
    public function test_vendor_manager_cannot_manage_staff(): void
    {
        $vendor = Vendor::factory()->create();
        $user = User::factory()->create();
        $vendor->memberships()->create([
            'user_id' => $user->id,
            'role' => 'vendor_manager',
            'status' => 'active',
        ]);

        $this->assertFalse($user->can('manageStaff', $vendor));
    }

    /**
     * Test: Vendor staff cannot manage staff.
     */
    public function test_vendor_staff_cannot_manage_staff(): void
    {
        $vendor = Vendor::factory()->create();
        $user = User::factory()->create();
        $vendor->memberships()->create([
            'user_id' => $user->id,
            'role' => 'vendor_staff',
            'status' => 'active',
        ]);

        $this->assertFalse($user->can('manageStaff', $vendor));
    }

    /**
     * Test: A user can access multiple vendors if they have memberships in them.
     */
    public function test_user_can_access_multiple_vendors_with_memberships(): void
    {
        $vendor1 = Vendor::factory()->create();
        $vendor2 = Vendor::factory()->create();
        $user = User::factory()->create();

        $vendor1->memberships()->create([
            'user_id' => $user->id,
            'role' => 'vendor_staff',
            'status' => 'active',
        ]);

        $vendor2->memberships()->create([
            'user_id' => $user->id,
            'role' => 'vendor_staff',
            'status' => 'active',
        ]);

        $this->assertTrue($user->can('view', $vendor1));
        $this->assertTrue($user->can('view', $vendor2));
    }

    /**
     * Test: Revoked membership removes vendor access immediately.
     */
    public function test_revoked_membership_removes_access(): void
    {
        $vendor = Vendor::factory()->create();
        $user = User::factory()->create();
        $membership = $vendor->memberships()->create([
            'user_id' => $user->id,
            'role' => 'vendor_staff',
            'status' => 'active',
        ]);

        // Initially can view
        $this->assertTrue($user->can('view', $vendor));

        // Revoke membership
        $membership->update(['status' => 'inactive']);

        // Now cannot view (no cache)
        $this->assertFalse($user->can('view', $vendor));
    }

    /**
     * Test: A user without any vendor memberships cannot access any vendor.
     */
    public function test_user_without_memberships_cannot_access_vendor(): void
    {
        $vendor = Vendor::factory()->create();
        $user = User::factory()->create();

        $this->assertFalse($user->can('view', $vendor));
        $this->assertFalse($user->can('update', $vendor));
        $this->assertFalse($user->can('manageStaff', $vendor));
    }

    /**
     * Test: User vendors relationship returns only active memberships.
     */
    public function test_user_vendors_returns_only_active_memberships(): void
    {
        $vendor1 = Vendor::factory()->create();
        $vendor2 = Vendor::factory()->create();
        $vendor3 = Vendor::factory()->create();
        $user = User::factory()->create();

        $vendor1->memberships()->create([
            'user_id' => $user->id,
            'role' => 'vendor_staff',
            'status' => 'active',
        ]);

        $vendor2->memberships()->create([
            'user_id' => $user->id,
            'role' => 'vendor_staff',
            'status' => 'inactive',
        ]);

        $vendor3->memberships()->create([
            'user_id' => $user->id,
            'role' => 'vendor_staff',
            'status' => 'active',
        ]);

        $userVendors = $user->vendors()->pluck('id')->toArray();

        $this->assertContains($vendor1->id, $userVendors);
        $this->assertNotContains($vendor2->id, $userVendors);
        $this->assertContains($vendor3->id, $userVendors);
        $this->assertCount(2, $userVendors);
    }
}
