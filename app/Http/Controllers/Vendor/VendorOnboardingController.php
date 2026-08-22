<?php

namespace App\Http\Controllers\Vendor;

use App\Domain\Vendors\Actions\StartVendorOnboardingAction;
use App\Domain\Vendors\Actions\StoreVendorKycDocumentAction;
use App\Domain\Vendors\Actions\UpdateVendorBusinessDetailsAction;
use App\Domain\Vendors\Actions\UpdateVendorGstDetailsAction;
use App\Domain\Vendors\Actions\UpdateVendorPrimaryContactAction;
use App\Http\Controllers\Controller;
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
        private readonly StoreVendorKycDocumentAction $storeVendorKycDocument,
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
        ]);
    }

    public function updateBusinessDetails(UpdateVendorBusinessDetailsRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->updateVendorBusinessDetails->execute($vendor, $request->businessDetails());

        return to_route('vendor.onboarding.show');
    }

    public function updateGstDetails(UpdateVendorGstDetailsRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->updateVendorGstDetails->execute($vendor, $request->gstDetails());

        return to_route('vendor.onboarding.show');
    }

    public function updatePrimaryContact(UpdateVendorPrimaryContactRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->updateVendorPrimaryContact->execute($vendor, $request->primaryContact());

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

        return to_route('vendor.onboarding.show');
    }
}
