<?php

namespace App\Domain\Vendors\Actions;

use App\Domain\Vendors\Enums\VendorStatus;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorStatusHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SuspendVendorAction
{
    public function execute(Vendor $vendor, User $reviewer, int $expectedSubmissionVersion, string $reasonCode, string $reasonMessage): Vendor
    {
        return DB::transaction(function () use ($vendor, $reviewer, $expectedSubmissionVersion, $reasonCode, $reasonMessage): Vendor {
            $vendor = Vendor::query()->lockForUpdate()->findOrFail($vendor->id);

            if ($vendor->status === VendorStatus::Suspended
                && $vendor->submission_version === $expectedSubmissionVersion) {
                return $vendor;
            }

            if (! in_array($vendor->status, [VendorStatus::Approved, VendorStatus::Inactive], true)
                || $vendor->submission_version !== $expectedSubmissionVersion) {
                throw ValidationException::withMessages([
                    'vendor' => 'This vendor is no longer available for suspension.',
                ]);
            }

            $previousStatus = $vendor->status;
            $vendor->forceFill(['status' => VendorStatus::Suspended])->save();

            VendorStatusHistory::query()->create([
                'vendor_id' => $vendor->id,
                'actor_user_id' => $reviewer->id,
                'sequence' => (int) VendorStatusHistory::query()
                    ->where('vendor_id', $vendor->id)
                    ->max('sequence') + 1,
                'from_status' => $previousStatus->value,
                'to_status' => VendorStatus::Suspended->value,
                'reason_code' => $reasonCode,
                'reason_message' => $reasonMessage,
                'transitioned_at' => now(),
            ]);

            return $vendor;
        });
    }
}
