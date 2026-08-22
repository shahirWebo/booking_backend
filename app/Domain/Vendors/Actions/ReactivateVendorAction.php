<?php

namespace App\Domain\Vendors\Actions;

use App\Domain\Vendors\Enums\VendorMembershipStatus;
use App\Domain\Vendors\Enums\VendorStatus;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorStatusHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ReactivateVendorAction
{
    public function execute(Vendor $vendor, User $reviewer, int $expectedSubmissionVersion, string $reasonMessage): Vendor
    {
        return DB::transaction(function () use ($vendor, $reviewer, $expectedSubmissionVersion, $reasonMessage): Vendor {
            $vendor = Vendor::query()->lockForUpdate()->findOrFail($vendor->id);

            if ($vendor->status === VendorStatus::Approved
                && $vendor->submission_version === $expectedSubmissionVersion) {
                return $vendor;
            }

            if ($vendor->status !== VendorStatus::Suspended
                || $vendor->submission_version !== $expectedSubmissionVersion) {
                throw ValidationException::withMessages([
                    'vendor' => 'This vendor is no longer available for reactivation.',
                ]);
            }

            $hasActiveOwner = $vendor->memberships()
                ->where('role', 'vendor_owner')
                ->where('status', VendorMembershipStatus::Active)
                ->exists();

            if (! $hasActiveOwner) {
                throw ValidationException::withMessages([
                    'vendor' => 'An active vendor owner is required before reactivation.',
                ]);
            }

            $vendor->forceFill(['status' => VendorStatus::Approved])->save();

            VendorStatusHistory::query()->create([
                'vendor_id' => $vendor->id,
                'actor_user_id' => $reviewer->id,
                'sequence' => (int) VendorStatusHistory::query()
                    ->where('vendor_id', $vendor->id)
                    ->max('sequence') + 1,
                'from_status' => VendorStatus::Suspended->value,
                'to_status' => VendorStatus::Approved->value,
                'reason_code' => 'reactivated',
                'reason_message' => $reasonMessage,
                'transitioned_at' => now(),
            ]);

            return $vendor;
        });
    }
}
