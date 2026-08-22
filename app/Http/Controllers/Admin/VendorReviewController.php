<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Vendors\Actions\ApproveVendorAction;
use App\Domain\Vendors\Enums\VendorStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveVendorRequest;
use App\Models\Vendor;
use App\Models\VendorSubmissionSnapshot;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class VendorReviewController extends Controller
{
    public function index(): Response
    {
        $vendors = Vendor::query()
            ->where('status', VendorStatus::PendingApproval)
            ->with(['submissionSnapshots' => fn ($query) => $query->orderByDesc('submission_version')])
            ->orderBy('updated_at')
            ->get()
            ->map(function (Vendor $vendor): array {
                $snapshot = $vendor->submissionSnapshots->first();

                return [
                    'id' => $vendor->id,
                    'display_name' => $vendor->display_name,
                    'legal_name' => $vendor->legal_name,
                    'submission_version' => $vendor->submission_version,
                    'submitted_at' => $snapshot?->submitted_at?->toIso8601String(),
                    'review_url' => route('admin.vendor_reviews.show', $vendor),
                ];
            })
            ->all();

        return Inertia::render('admin/operations/VendorReviewIndex', [
            'vendors' => $vendors,
        ]);
    }

    public function show(Vendor $vendor): Response
    {
        abort_unless($vendor->status === VendorStatus::PendingApproval, 404);

        $snapshot = VendorSubmissionSnapshot::query()
            ->where('vendor_id', $vendor->id)
            ->where('submission_version', $vendor->submission_version)
            ->firstOrFail();

        $bankAccount = $vendor->bankAccounts()
            ->whereKey($snapshot->snapshot['bank_account_id'] ?? null)
            ->first();

        $documents = $vendor->documents()
            ->whereIn('id', array_values($snapshot->snapshot['document_ids'] ?? []))
            ->orderBy('document_type')
            ->get(['id', 'document_type', 'status']);

        return Inertia::render('admin/operations/VendorReview', [
            'vendor' => [
                'id' => $vendor->id,
                'submission_version' => $vendor->submission_version,
                'submitted_at' => $snapshot->submitted_at->toIso8601String(),
                'business' => $snapshot->snapshot['business'],
                'primary_contact' => $snapshot->snapshot['primary_contact'],
                'gst' => $snapshot->snapshot['gst'],
                'bank_account' => $bankAccount === null ? null : [
                    'bank_name' => $bankAccount->bank_name,
                    'account_number_last_four' => $bankAccount->account_number_last_four,
                    'country_code' => $bankAccount->country_code,
                    'currency' => $bankAccount->currency,
                ],
                'documents' => $documents->map(fn ($document): array => [
                    'id' => $document->id,
                    'document_type' => $document->document_type,
                    'status' => $document->status,
                ])->all(),
            ],
            'routes' => [
                'index' => route('admin.vendor_reviews.index'),
                'approve' => route('admin.vendor_reviews.approve', $vendor),
            ],
        ]);
    }

    public function approve(
        ApproveVendorRequest $request,
        Vendor $vendor,
        ApproveVendorAction $approveVendor,
    ): RedirectResponse {
        $approveVendor->execute($vendor, $request->user(), $request->submissionVersion());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vendor approved.')]);

        return to_route('admin.vendor_reviews.index');
    }
}
