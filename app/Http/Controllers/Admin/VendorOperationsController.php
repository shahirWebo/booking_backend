<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Vendors\Actions\ReactivateVendorAction;
use App\Domain\Vendors\Actions\SuspendVendorAction;
use App\Domain\Vendors\Enums\VendorStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReactivateVendorRequest;
use App\Http\Requests\Admin\SuspendVendorRequest;
use App\Models\Vendor;
use App\Models\VendorStatusHistory;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class VendorOperationsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/operations/VendorLifecycleIndex', [
            'vendors' => Vendor::query()
                ->whereIn('status', [VendorStatus::Approved, VendorStatus::Inactive, VendorStatus::Suspended])
                ->orderBy('display_name')
                ->get(['id', 'display_name', 'legal_name', 'status', 'submission_version'])
                ->map(fn (Vendor $vendor): array => [
                    'id' => $vendor->id,
                    'display_name' => $vendor->display_name,
                    'legal_name' => $vendor->legal_name,
                    'status' => $vendor->status->value,
                    'operations_url' => route('admin.vendor_operations.show', $vendor),
                ])
                ->all(),
        ]);
    }

    public function show(Vendor $vendor): Response
    {
        abort_unless(in_array($vendor->status, [VendorStatus::Approved, VendorStatus::Inactive, VendorStatus::Suspended], true), 404);

        $lastTransition = VendorStatusHistory::query()
            ->where('vendor_id', $vendor->id)
            ->orderByDesc('sequence')
            ->first();

        return Inertia::render('admin/operations/VendorLifecycle', [
            'vendor' => [
                'id' => $vendor->id,
                'display_name' => $vendor->display_name,
                'legal_name' => $vendor->legal_name,
                'status' => $vendor->status->value,
                'submission_version' => $vendor->submission_version,
                'last_transitioned_at' => $lastTransition?->transitioned_at?->toIso8601String(),
            ],
            'permissions' => [
                'can_suspend' => request()->user()?->hasPermission('suspend_vendors') === true,
                'can_reactivate' => request()->user()?->hasPermission('reactivate_vendors') === true,
            ],
            'routes' => [
                'index' => route('admin.vendor_operations.index'),
                'suspend' => route('admin.vendor_operations.suspend', $vendor),
                'reactivate' => route('admin.vendor_operations.reactivate', $vendor),
            ],
        ]);
    }

    public function suspend(
        SuspendVendorRequest $request,
        Vendor $vendor,
        SuspendVendorAction $suspendVendor,
    ): RedirectResponse {
        $suspendVendor->execute(
            $vendor,
            $request->user(),
            $request->submissionVersion(),
            $request->reasonCode(),
            $request->reasonMessage(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vendor suspended.')]);

        return to_route('admin.vendor_operations.index');
    }

    public function reactivate(
        ReactivateVendorRequest $request,
        Vendor $vendor,
        ReactivateVendorAction $reactivateVendor,
    ): RedirectResponse {
        $reactivateVendor->execute(
            $vendor,
            $request->user(),
            $request->submissionVersion(),
            $request->reasonMessage(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vendor reactivated.')]);

        return to_route('admin.vendor_operations.index');
    }
}
