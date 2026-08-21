<?php

namespace App\Http\Controllers\Vendor;

use App\Domain\Vendors\Actions\StartVendorOnboardingAction;
use App\Domain\Vendors\Actions\UpdateVendorBusinessDetailsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\UpdateVendorBusinessDetailsRequest;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class VendorOnboardingController extends Controller
{
    public function __construct(
        private readonly StartVendorOnboardingAction $startVendorOnboarding,
        private readonly UpdateVendorBusinessDetailsAction $updateVendorBusinessDetails,
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
                'submission_version' => $vendor->submission_version,
            ],
            'owner' => [
                'name' => $request->user()->name,
                'mobile_number' => $request->user()->mobile_number,
                'email' => $request->user()->email,
            ],
        ]);
    }

    public function updateBusinessDetails(UpdateVendorBusinessDetailsRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->updateVendorBusinessDetails->execute($vendor, $request->businessDetails());

        return to_route('vendor.onboarding.show');
    }
}
