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

final class SubmitVendorOnboardingAction
{
    public function execute(Vendor $vendor, User $actor, int $expectedSubmissionVersion): Vendor
    {
        return DB::transaction(function () use ($vendor, $actor, $expectedSubmissionVersion): Vendor {
            $vendor = Vendor::query()->lockForUpdate()->findOrFail($vendor->id);

            if ($vendor->status === VendorStatus::PendingApproval
                && $vendor->submission_version === $expectedSubmissionVersion) {
                return $vendor;
            }

            if ($vendor->status !== VendorStatus::Draft) {
                throw ValidationException::withMessages([
                    'vendor' => 'This vendor registration is not available for submission.',
                ]);
            }

            if ($vendor->submission_version !== $expectedSubmissionVersion) {
                throw ValidationException::withMessages([
                    'submission_version' => 'This draft has changed. Refresh and submit the current version.',
                ]);
            }

            $documents = $this->readyDocuments($vendor);
            $bankAccount = VendorBankAccount::query()
                ->where('vendor_id', $vendor->id)
                ->where('submission_version', $vendor->submission_version)
                ->where('status', 'active')
                ->first();

            $this->ensureComplete($vendor, $documents, $bankAccount);

            VendorSubmissionSnapshot::query()->create([
                'vendor_id' => $vendor->id,
                'submission_version' => $vendor->submission_version,
                'submitted_by_user_id' => $actor->id,
                'snapshot' => $this->snapshot($vendor, $documents, $bankAccount),
                'submitted_at' => now(),
            ]);

            $vendor->forceFill(['status' => VendorStatus::PendingApproval])->save();

            VendorStatusHistory::query()->create([
                'vendor_id' => $vendor->id,
                'actor_user_id' => $actor->id,
                'sequence' => (int) VendorStatusHistory::query()
                    ->where('vendor_id', $vendor->id)
                    ->max('sequence') + 1,
                'from_status' => VendorStatus::Draft->value,
                'to_status' => VendorStatus::PendingApproval->value,
                'reason_code' => 'submitted',
                'transitioned_at' => now(),
            ]);

            return $vendor;
        });
    }

    /**
     * @return array<string, VendorDocument>
     */
    private function readyDocuments(Vendor $vendor): array
    {
        return VendorDocument::query()
            ->with('file')
            ->where('vendor_id', $vendor->id)
            ->where('submission_version', $vendor->submission_version)
            ->where('status', 'active')
            ->whereHas('file', function ($query) use ($vendor): void {
                $query
                    ->where('vendor_id', $vendor->id)
                    ->where('purpose', FilePurpose::VendorKycDocument->value)
                    ->where('status', FileStatus::Ready->value);
            })
            ->get()
            ->keyBy('document_type')
            ->all();
    }

    /**
     * @param  array<string, VendorDocument>  $documents
     */
    private function ensureComplete(Vendor $vendor, array $documents, ?VendorBankAccount $bankAccount): void
    {
        $missing = [];

        if ($vendor->legal_name === null || $vendor->display_name === null || $vendor->legal_entity_type === null) {
            $missing[] = 'business details';
        }

        if ($vendor->primary_contact_name === null
            || $vendor->primary_contact_email === null
            || $vendor->primary_contact_mobile_number === null) {
            $missing[] = 'primary contact details';
        }

        if ($vendor->is_gst_registered === null) {
            $missing[] = 'GST registration status';
        }

        if ($vendor->is_gst_registered === true && $vendor->gstin === null) {
            $missing[] = 'GSTIN';
        }

        if ($bankAccount === null) {
            $missing[] = 'bank account details';
        }

        $requiredDocumentTypes = config('vendor_onboarding.required_document_types');

        if ($vendor->is_gst_registered === true) {
            $requiredDocumentTypes[] = config('vendor_onboarding.gst_document_type');
        }

        foreach ($requiredDocumentTypes as $documentType) {
            if (! isset($documents[$documentType])) {
                $missing[] = str_replace('_', ' ', (string) $documentType);
            }
        }

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'submission' => 'Complete the following before submitting: '.implode(', ', $missing).'.',
            ]);
        }
    }

    /**
     * @param  array<string, VendorDocument>  $documents
     * @return array<string, mixed>
     */
    private function snapshot(Vendor $vendor, array $documents, VendorBankAccount $bankAccount): array
    {
        return [
            'business' => [
                'legal_name' => $vendor->legal_name,
                'display_name' => $vendor->display_name,
                'legal_entity_type' => $vendor->legal_entity_type,
            ],
            'primary_contact' => [
                'name' => $vendor->primary_contact_name,
                'email' => $vendor->primary_contact_email,
                'mobile_number' => $vendor->primary_contact_mobile_number,
            ],
            'gst' => [
                'is_registered' => $vendor->is_gst_registered,
                'gstin' => $vendor->gstin,
            ],
            'bank_account_id' => $bankAccount->id,
            'document_ids' => collect($documents)
                ->map(fn (VendorDocument $document): int => $document->id)
                ->all(),
        ];
    }
}
