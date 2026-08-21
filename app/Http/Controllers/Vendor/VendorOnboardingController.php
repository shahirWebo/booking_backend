<?php

namespace App\Http\Controllers\Vendor;

use App\Domain\Vendors\Actions\StartVendorOnboardingAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class VendorOnboardingController extends Controller
{
    public function __construct(
        private readonly StartVendorOnboardingAction $startVendorOnboarding,
    ) {}

    public function show(Request $request): InertiaResponse
    {
        $vendor = $this->startVendorOnboarding->execute($request->user());

        return Inertia::render('vendor/Onboarding', [
            'vendor' => [
                'id' => $vendor->id,
                'status' => $vendor->status->value,
                'submission_version' => $vendor->submission_version,
            ],
            'owner' => [
                'name' => $request->user()->name,
                'mobile_number' => $request->user()->mobile_number,
                'email' => $request->user()->email,
            ],
        ]);
    }
}
