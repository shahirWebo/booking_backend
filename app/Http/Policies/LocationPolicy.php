<?php

namespace App\Http\Policies;

use App\Models\Location;
use App\Models\User;

class LocationPolicy
{
    public function view(User $user, Location $location): bool
    {
        return $this->isMember($user, $location->vendor_id);
    }

    public function update(User $user, Location $location): bool
    {
        return $this->isMemberWithRole($user, $location->vendor_id, ['vendor_owner', 'vendor_manager']);
    }

    private function isMember(User $user, int $vendorId): bool
    {
        return $user->vendorMemberships()
            ->where('vendor_id', $vendorId)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * @param  array<string>  $roles
     */
    private function isMemberWithRole(User $user, int $vendorId, array $roles): bool
    {
        return $user->vendorMemberships()
            ->where('vendor_id', $vendorId)
            ->where('status', 'active')
            ->whereIn('role', $roles)
            ->exists();
    }
}
