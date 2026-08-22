<?php

namespace App\Domain\Vendors\Actions;

use App\Domain\Vendors\Enums\VendorStatus;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorStatusHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RejectVendorAction
{
    public function execute(
        Vendor $vendor,
        User $reviewer,
        int $expectedSubmissionVersion,
        string $reasonCode,
        string $reasonMessage,
    ): Vendor {
        return DB::transaction(function () use ($vendor, $reviewer, $expectedSubmissionVersion, $reasonCode, $reasonMessage): Vendor {
            $vendor = Vendor::query()->lockForUpdate()->findOrFail($vendor->id);

            if ($vendor->status === VendorStatus::Rejected
                && $vendor->submission_version === $expectedSubmissionVersion) {
                return $vendor;
            }

            if ($vendor->status !== VendorStatus::PendingApproval
                || $vendor->submission_version !== $expectedSubmissionVersion) {
                throw ValidationException::withMessages([
                    'vendor' => 'This vendor submission is no longer available for rejection.',
                ]);
            }

            $vendor->forceFill(['status' => VendorStatus::Rejected])->save();

            VendorStatusHistory::query()->create([
                'vendor_id' => $vendor->id,
                'actor_user_id' => $reviewer->id,
                'sequence' => (int) VendorStatusHistory::query()
                    ->where('vendor_id', $vendor->id)
                    ->max('sequence') + 1,
                'from_status' => VendorStatus::PendingApproval->value,
                'to_status' => VendorStatus::Rejected->value,
                'reason_code' => $reasonCode,
                'reason_message' => $reasonMessage,
                'transitioned_at' => now(),
            ]);

            return $vendor;
        });
    }
}
