<?php

namespace App\Domain\Vendors\Actions;

use App\Domain\Vendors\Enums\VendorStatus;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorStatusHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PrepareRejectedVendorResubmissionAction
{
    public function execute(Vendor $vendor, User $owner, int $expectedSubmissionVersion): Vendor
    {
        return DB::transaction(function () use ($vendor, $owner, $expectedSubmissionVersion): Vendor {
            $vendor = Vendor::query()->lockForUpdate()->findOrFail($vendor->id);

            if ($vendor->status === VendorStatus::Draft
                && $vendor->submission_version === $expectedSubmissionVersion + 1) {
                return $vendor;
            }

            if ($vendor->status !== VendorStatus::Rejected
                || $vendor->submission_version !== $expectedSubmissionVersion) {
                throw ValidationException::withMessages([
                    'vendor' => 'This rejected registration is no longer available for resubmission.',
                ]);
            }

            $vendor->forceFill([
                'status' => VendorStatus::Draft,
                'submission_version' => $vendor->submission_version + 1,
            ])->save();

            VendorStatusHistory::query()->create([
                'vendor_id' => $vendor->id,
                'actor_user_id' => $owner->id,
                'sequence' => (int) VendorStatusHistory::query()
                    ->where('vendor_id', $vendor->id)
                    ->max('sequence') + 1,
                'from_status' => VendorStatus::Rejected->value,
                'to_status' => VendorStatus::Draft->value,
                'reason_code' => 'resubmission_started',
                'transitioned_at' => now(),
            ]);

            return $vendor;
        });
    }
}
