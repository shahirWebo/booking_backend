<?php

namespace App\Http\Policies;

use App\Models\User;
use App\Models\Vendor;

/**
 * Vendor Authorization Policy
 *
 * Enforces vendor resource ownership and cross-vendor isolation.
 * A user can only access a vendor if they have an active membership in it.
 */
class VendorPolicy
{
    /**
     * Determine if the user can view the vendor.
     * Vendor staff can only view their own vendor.
     */
    public function view(User $user, Vendor $vendor): bool
    {
        return $this->isMember($user, $vendor);
    }

    /**
     * Determine if the user can update the vendor.
     * Only vendor owners/managers can update vendor details.
     */
    public function update(User $user, Vendor $vendor): bool
    {
        return $this->isMemberWithRole($user, $vendor, ['vendor_owner', 'vendor_manager']);
    }

    /**
     * Determine if the user can manage vendor staff.
     * Only vendor owners can manage staff memberships.
     */
    public function manageStaff(User $user, Vendor $vendor): bool
    {
        return $this->isMemberWithRole($user, $vendor, ['vendor_owner']);
    }

    /**
     * Check if the user is an active member of the vendor.
     */
    private function isMember(User $user, Vendor $vendor): bool
    {
        return $user->vendorMemberships()
            ->where('vendor_id', $vendor->id)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Check if the user is an active member of the vendor with one of the specified roles.
     *
     * @param  array<string>  $roles
     */
    private function isMemberWithRole(User $user, Vendor $vendor, array $roles): bool
    {
        return $user->vendorMemberships()
            ->where('vendor_id', $vendor->id)
            ->where('status', 'active')
            ->whereIn('role', $roles)
            ->exists();
    }
}
