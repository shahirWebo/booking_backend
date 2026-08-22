<?php

namespace App\Domain\Locations\Actions;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Auth\Access\AuthorizationException;

final class ResolveManagedVendorAction
{
    /**
     * Resolve the first vendor the actor can manage until multi-vendor selection is introduced.
     *
     * @throws AuthorizationException
     */
    public function execute(User $user): Vendor
    {
        $membership = $user->vendorMemberships()
            ->with('vendor')
            ->where('status', 'active')
            ->whereIn('role', ['vendor_owner', 'vendor_manager'])
            ->orderBy('vendor_id')
            ->first();

        if ($membership?->vendor instanceof Vendor) {
            return $membership->vendor;
        }

        throw new AuthorizationException('You do not have access to manage vendor locations.');
    }
}
