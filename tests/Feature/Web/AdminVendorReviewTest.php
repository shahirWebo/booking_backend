<?php

use App\Domain\Files\Enums\FilePurpose;
use App\Domain\Files\Enums\FileStatus;
use App\Domain\Users\Enums\UserStatus;
use App\Domain\Vendors\Enums\VendorStatus;
use App\Models\File;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorBankAccount;
use App\Models\VendorDocument;
use App\Models\VendorStatusHistory;
use App\Models\VendorSubmissionSnapshot;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(DatabaseSeeder::class)->run();
});

test('only vendor reviewers can access the admin vendor review workflow', function (): void {
    $supportUser = adminUser('admin_support');

    $this->actingAs($supportUser)
        ->get(route('admin.vendor_reviews.index'))
        ->assertForbidden();
});

test('vendor reviewers can inspect pending submissions without raw bank credentials', function (): void {
    $reviewer = adminUser('admin_operations');
    $vendor = pendingVendorReviewFixture();

    $this->actingAs($reviewer)
        ->get(route('admin.vendor_reviews.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/operations/VendorReviewIndex')
            ->has('vendors', 1)
            ->where('vendors.0.id', $vendor->id),
        );

    $this->get(route('admin.vendor_reviews.show', $vendor))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/operations/VendorReview')
            ->where('vendor.business.legal_name', 'Acme Sports Private Limited')
            ->where('vendor.bank_account.account_number_last_four', '3456')
            ->missing('vendor.bank_account.account_number_encrypted')
            ->missing('vendor.bank_account.routing_code_encrypted'),
        );
});

test('a reviewer can approve a pending vendor submission exactly once', function (): void {
    $reviewer = adminUser('admin_operations');
    $vendor = pendingVendorReviewFixture();

    $this->actingAs($reviewer)
        ->post(route('admin.vendor_reviews.approve', $vendor), ['submission_version' => 1])
        ->assertRedirect(route('admin.vendor_reviews.index'));

    $this->post(route('admin.vendor_reviews.approve', $vendor), ['submission_version' => 1])
        ->assertRedirect(route('admin.vendor_reviews.index'));

    expect($vendor->fresh()->status)->toBe(VendorStatus::Approved)
        ->and(VendorStatusHistory::query()->where('vendor_id', $vendor->id)->where('to_status', VendorStatus::Approved->value)->count())->toBe(1);
});

test('a reviewer cannot approve a submission whose KYC evidence is no longer ready', function (): void {
    $reviewer = adminUser('admin_operations');
    $vendor = pendingVendorReviewFixture();
    File::query()->where('vendor_id', $vendor->id)->firstOrFail()->update(['status' => FileStatus::Failed]);

    $this->actingAs($reviewer)
        ->from(route('admin.vendor_reviews.show', $vendor))
        ->post(route('admin.vendor_reviews.approve', $vendor), ['submission_version' => 1])
        ->assertRedirect(route('admin.vendor_reviews.show', $vendor))
        ->assertSessionHasErrors('vendor');

    expect($vendor->fresh()->status)->toBe(VendorStatus::PendingApproval);
});

function adminUser(string $roleCode): User
{
    $user = User::factory()->create(['status' => UserStatus::Active]);
    $user->roles()->attach(Role::query()->where('code', $roleCode)->firstOrFail());

    return $user;
}

function pendingVendorReviewFixture(): Vendor
{
    $vendor = Vendor::factory()->create([
        'status' => VendorStatus::PendingApproval,
        'legal_name' => 'Acme Sports Private Limited',
        'display_name' => 'Acme Sports Arena',
        'legal_entity_type' => 'private_limited_company',
        'primary_contact_name' => 'Riya Sharma',
        'primary_contact_email' => 'owner@example.test',
        'primary_contact_mobile_number' => '+919900001111',
        'is_gst_registered' => false,
    ]);

    $account = VendorBankAccount::query()->create([
        'vendor_id' => $vendor->id,
        'account_holder_name' => 'Acme Sports Private Limited',
        'bank_name' => 'Example Bank',
        'account_number_encrypted' => '1234567890123456',
        'account_number_last_four' => '3456',
        'routing_code_encrypted' => 'EXAM0000123',
        'country_code' => 'IN',
        'currency' => 'INR',
    ]);

    $documentIds = [];

    foreach (['identity_proof', 'business_registration'] as $documentType) {
        $file = File::query()->create([
            'purpose' => FilePurpose::VendorKycDocument,
            'status' => FileStatus::Ready,
            'vendor_id' => $vendor->id,
            'logical_disk' => 'private_files',
            'object_key' => "vendor_kyc_document/2026/08/review-{$documentType}/source",
        ]);
        $document = VendorDocument::query()->create([
            'vendor_id' => $vendor->id,
            'file_id' => $file->id,
            'document_type' => $documentType,
            'status' => 'active',
        ]);
        $documentIds[$documentType] = $document->id;
    }

    VendorSubmissionSnapshot::query()->create([
        'vendor_id' => $vendor->id,
        'submission_version' => 1,
        'snapshot' => [
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
            'gst' => ['is_registered' => false, 'gstin' => null],
            'bank_account_id' => $account->id,
            'document_ids' => $documentIds,
        ],
        'submitted_at' => now(),
    ]);

    VendorStatusHistory::query()->create([
        'vendor_id' => $vendor->id,
        'sequence' => 1,
        'to_status' => VendorStatus::PendingApproval->value,
        'transitioned_at' => now(),
    ]);

    return $vendor;
}
