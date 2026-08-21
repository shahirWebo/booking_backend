<?php

namespace App\Domain\Vendors\Actions;

use App\Domain\Vendors\Enums\VendorStatus;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorMembership;
use App\Models\VendorStatusHistory;
use Illuminate\Support\Facades\DB;

final class StartVendorOnboardingAction
{
    public function execute(User $user): Vendor
    {
        return DB::transaction(function () use ($user): Vendor {
            $membership = VendorMembership::query()
                ->with('vendor')
                ->where('user_id', $user->id)
                ->where('role', 'vendor_owner')
                ->where('status', 'active')
                ->orderBy('id')
                ->first();

            if ($membership !== null) {
                return $membership->vendor;
            }

            $vendor = Vendor::query()->create([
                'status' => VendorStatus::Draft,
                'submission_version' => 1,
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

            return $vendor;
        });
    }
}
