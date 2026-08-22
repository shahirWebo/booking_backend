<?php

namespace App\Domain\Vendors\Actions;

use App\Domain\Files\Enums\FilePurpose;
use App\Domain\Files\Enums\FileStatus;
use App\Domain\Vendors\Enums\VendorStatus;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorBankAccount;
use App\Models\VendorDocument;
use App\Models\VendorStatusHistory;
use App\Models\VendorSubmissionSnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ApproveVendorAction
{
    public function execute(Vendor $vendor, User $reviewer, int $expectedSubmissionVersion): Vendor
    {
        return DB::transaction(function () use ($vendor, $reviewer, $expectedSubmissionVersion): Vendor {
            $vendor = Vendor::query()->lockForUpdate()->findOrFail($vendor->id);

            if ($vendor->status === VendorStatus::Approved
                && $vendor->submission_version === $expectedSubmissionVersion) {
                return $vendor;
            }

            if ($vendor->status !== VendorStatus::PendingApproval
                || $vendor->submission_version !== $expectedSubmissionVersion) {
                throw ValidationException::withMessages([
                    'vendor' => 'This vendor submission is no longer available for approval.',
                ]);
            }

            $snapshot = VendorSubmissionSnapshot::query()
                ->where('vendor_id', $vendor->id)
                ->where('submission_version', $vendor->submission_version)
                ->lockForUpdate()
                ->first();

            if (! $snapshot instanceof VendorSubmissionSnapshot || ! $this->snapshotEvidenceIsReady($vendor, $snapshot)) {
                throw ValidationException::withMessages([
                    'vendor' => 'The submitted evidence is no longer ready for approval.',
                ]);
            }

            $vendor->forceFill(['status' => VendorStatus::Approved])->save();

            VendorStatusHistory::query()->create([
                'vendor_id' => $vendor->id,
                'actor_user_id' => $reviewer->id,
                'sequence' => (int) VendorStatusHistory::query()
                    ->where('vendor_id', $vendor->id)
                    ->max('sequence') + 1,
                'from_status' => VendorStatus::PendingApproval->value,
                'to_status' => VendorStatus::Approved->value,
                'reason_code' => 'approved',
                'transitioned_at' => now(),
            ]);

            return $vendor;
        });
    }

    private function snapshotEvidenceIsReady(Vendor $vendor, VendorSubmissionSnapshot $snapshot): bool
    {
        $documentIds = $snapshot->snapshot['document_ids'] ?? null;
        $bankAccountId = $snapshot->snapshot['bank_account_id'] ?? null;

        if (! is_array($documentIds) || $documentIds === [] || ! is_int($bankAccountId)) {
            return false;
        }

        $readyDocuments = VendorDocument::query()
            ->where('vendor_id', $vendor->id)
            ->where('submission_version', $snapshot->submission_version)
            ->where('status', 'active')
            ->whereIn('id', array_values($documentIds))
            ->whereHas('file', function ($query) use ($vendor): void {
                $query
                    ->where('vendor_id', $vendor->id)
                    ->where('purpose', FilePurpose::VendorKycDocument->value)
                    ->where('status', FileStatus::Ready->value);
            })
            ->count();

        $bankAccountIsActive = VendorBankAccount::query()
            ->whereKey($bankAccountId)
            ->where('vendor_id', $vendor->id)
            ->where('submission_version', $snapshot->submission_version)
            ->where('status', 'active')
            ->exists();

        return $readyDocuments === count($documentIds) && $bankAccountIsActive;
    }
}
