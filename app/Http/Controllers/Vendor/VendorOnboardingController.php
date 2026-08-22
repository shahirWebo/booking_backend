<?php

namespace App\Http\Controllers\Vendor;

use App\Domain\Vendors\Actions\PrepareRejectedVendorResubmissionAction;
use App\Domain\Vendors\Actions\StartVendorOnboardingAction;
use App\Domain\Vendors\Actions\StoreVendorBankAccountAction;
use App\Domain\Vendors\Actions\StoreVendorKycDocumentAction;
use App\Domain\Vendors\Actions\SubmitVendorOnboardingAction;
use App\Domain\Vendors\Actions\UpdateVendorBusinessDetailsAction;
use App\Domain\Vendors\Actions\UpdateVendorGstDetailsAction;
use App\Domain\Vendors\Actions\UpdateVendorPrimaryContactAction;
use App\Domain\Vendors\Enums\VendorDocumentType;
use App\Domain\Vendors\Enums\VendorStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\PrepareRejectedVendorResubmissionRequest;
use App\Http\Requests\Vendor\StoreVendorBankAccountRequest;
use App\Http\Requests\Vendor\SubmitVendorOnboardingRequest;
use App\Http\Requests\Vendor\UpdateVendorBusinessDetailsRequest;
use App\Http\Requests\Vendor\UpdateVendorGstDetailsRequest;
use App\Http\Requests\Vendor\UpdateVendorPrimaryContactRequest;
use App\Http\Requests\Vendor\UploadVendorKycDocumentRequest;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class VendorOnboardingController extends Controller
{
    public function __construct(
        private readonly StartVendorOnboardingAction $startVendorOnboarding,
        private readonly PrepareRejectedVendorResubmissionAction $prepareRejectedVendorResubmission,
        private readonly StoreVendorBankAccountAction $storeVendorBankAccount,
        private readonly StoreVendorKycDocumentAction $storeVendorKycDocument,
        private readonly SubmitVendorOnboardingAction $submitVendorOnboarding,
        private readonly UpdateVendorBusinessDetailsAction $updateVendorBusinessDetails,
        private readonly UpdateVendorGstDetailsAction $updateVendorGstDetails,
        private readonly UpdateVendorPrimaryContactAction $updateVendorPrimaryContact,
    ) {}

    public function show(Request $request): InertiaResponse
    {
        $vendor = $this->startVendorOnboarding->execute($request->user());

        return Inertia::render('vendor/Onboarding', [
            'vendor' => [
                'id' => $vendor->id,
                'status' => $vendor->status->value,
                'legal_name' => $vendor->legal_name,
                'display_name' => $vendor->display_name,
                'legal_entity_type' => $vendor->legal_entity_type,
                'primary_contact_name' => $vendor->primary_contact_name,
                'primary_contact_email' => $vendor->primary_contact_email,
                'primary_contact_mobile_number' => $vendor->primary_contact_mobile_number,
                'is_gst_registered' => $vendor->is_gst_registered,
                'gstin' => $vendor->gstin,
                'submission_version' => $vendor->submission_version,
                'can_edit' => in_array($vendor->status, [VendorStatus::Draft, VendorStatus::Rejected], true),
            ],
            'owner' => [
                'name' => $request->user()->name,
                'mobile_number' => $request->user()->mobile_number,
                'email' => $request->user()->email,
            ],
            'kycDocuments' => $vendor->documents()
                ->with('file')
                ->orderBy('document_type')
                ->get()
                ->map(fn ($document): array => [
                    'id' => $document->id,
                    'document_type' => $document->document_type,
                    'status' => $document->status,
                    'file_status' => $document->file?->status?->value,
                ])
                ->all(),
            'bankAccounts' => $vendor->bankAccounts()
                ->orderBy('id')
                ->get()
                ->map(fn ($account): array => [
                    'id' => $account->id,
                    'bank_name' => $account->bank_name,
                    'account_number_last_four' => $account->account_number_last_four,
                    'country_code' => $account->country_code,
                    'currency' => $account->currency,
                    'status' => $account->status,
                ])
                ->all(),
            'documentTypes' => collect(VendorDocumentType::cases())
                ->map(fn (VendorDocumentType $type): array => [
                    'value' => $type->value,
                    'label' => str($type->value)->replace('_', ' ')->title()->toString(),
                ])
                ->all(),
            'rejection' => $vendor->status === VendorStatus::Rejected
                ? $vendor->statusHistories()
                    ->where('to_status', VendorStatus::Rejected->value)
                    ->orderByDesc('sequence')
                    ->first(['reason_code', 'reason_message', 'transitioned_at'])
                : null,
            'routes' => [
                'business_details' => route('vendor.onboarding.business-details.update', $vendor),
                'primary_contact' => route('vendor.onboarding.primary-contact.update', $vendor),
                'gst_details' => route('vendor.onboarding.gst-details.update', $vendor),
                'kyc_documents' => route('vendor.onboarding.kyc-documents.store', $vendor),
                'bank_accounts' => route('vendor.onboarding.bank-accounts.store', $vendor),
                'submit' => route('vendor.onboarding.submit', $vendor),
                'prepare_resubmission' => route('vendor.onboarding.resubmission.prepare', $vendor),
            ],
        ]);
    }

    public function updateBusinessDetails(UpdateVendorBusinessDetailsRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->updateVendorBusinessDetails->execute($vendor, $request->businessDetails());
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Business details submitted .'),
        ]);

        return to_route('vendor.onboarding.show');
    }

    public function updateGstDetails(UpdateVendorGstDetailsRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->updateVendorGstDetails->execute($vendor, $request->gstDetails());
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('GST details submitted .'),
        ]);

        return to_route('vendor.onboarding.show');
    }

    public function updatePrimaryContact(UpdateVendorPrimaryContactRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->updateVendorPrimaryContact->execute($vendor, $request->primaryContact());
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Primary contact details submitted .'),
        ]);

        return to_route('vendor.onboarding.show');
    }

    public function uploadKycDocument(UploadVendorKycDocumentRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->storeVendorKycDocument->execute(
            $vendor,
            $request->user(),
            $request->documentType(),
            $request->document(),
        );
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('KYC document uploaded successfully.'),
        ]);

        return to_route('vendor.onboarding.show');
    }

    public function storeBankAccount(StoreVendorBankAccountRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->storeVendorBankAccount->execute($vendor, $request->bankAccountDetails());
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Bank account details submitted .'),
        ]);

        return to_route('vendor.onboarding.show');
    }

    public function submit(SubmitVendorOnboardingRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->submitVendorOnboarding->execute(
            $vendor,
            $request->user(),
            $request->submissionVersion(),
        );

        return to_route('vendor.onboarding.show');
    }

    public function prepareResubmission(
        PrepareRejectedVendorResubmissionRequest $request,
        Vendor $vendor,
    ): RedirectResponse {
        $this->prepareRejectedVendorResubmission->execute(
            $vendor,
            $request->user(),
            $request->submissionVersion(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Registration reopened for updates.')]);

        return to_route('vendor.onboarding.show');
    }
}
