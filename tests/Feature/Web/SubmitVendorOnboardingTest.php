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
use App\Models\VendorMembership;
use App\Models\VendorStatusHistory;
use App\Models\VendorSubmissionSnapshot;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an active vendor owner can submit a complete draft for approval', function (): void {
    [$user, $vendor] = completeVendorDraft();

    $this->actingAs($user)
        ->post(route('vendor.onboarding.submit', $vendor), ['submission_version' => 1])
        ->assertRedirect(route('vendor.onboarding.show'));

    $vendor->refresh();
    $snapshot = VendorSubmissionSnapshot::query()->sole();
    $history = VendorStatusHistory::query()->orderByDesc('sequence')->firstOrFail();

    expect($vendor->status)->toBe(VendorStatus::PendingApproval)
        ->and($snapshot->vendor_id)->toBe($vendor->id)
        ->and($snapshot->submission_version)->toBe(1)
        ->and($snapshot->submitted_by_user_id)->toBe($user->id)
        ->and($snapshot->snapshot['business']['legal_name'])->toBe('Acme Sports Private Limited')
        ->and($snapshot->snapshot['primary_contact']['email'])->toBe('owner@example.test')
        ->and($snapshot->snapshot['gst']['is_registered'])->toBeFalse()
        ->and($snapshot->snapshot)->not->toHaveKey('account_number')
        ->and($history->sequence)->toBe(2)
        ->and($history->from_status)->toBe(VendorStatus::Draft->value)
        ->and($history->to_status)->toBe(VendorStatus::PendingApproval->value)
        ->and($history->reason_code)->toBe('submitted');
});

test('a vendor draft cannot be submitted until its required evidence is ready', function (): void {
    $user = User::factory()->create(['status' => UserStatus::Active]);
    $vendor = Vendor::factory()->create([
        'legal_name' => 'Acme Sports Private Limited',
        'display_name' => 'Acme Sports Arena',
        'legal_entity_type' => 'private_limited_company',
        'primary_contact_name' => 'Riya Sharma',
        'primary_contact_email' => 'owner@example.test',
        'primary_contact_mobile_number' => '+919900001111',
        'is_gst_registered' => false,
    ]);

    VendorMembership::query()->create([
        'vendor_id' => $vendor->id,
        'user_id' => $user->id,
        'role' => 'vendor_owner',
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->from(route('vendor.onboarding.show'))
        ->post(route('vendor.onboarding.submit', $vendor), ['submission_version' => 1])
        ->assertRedirect(route('vendor.onboarding.show'))
        ->assertSessionHasErrors('submission');

    expect($vendor->fresh()->status)->toBe(VendorStatus::Draft)
        ->and(VendorSubmissionSnapshot::query()->count())->toBe(0);
});

test('vendor submission rejects a stale draft version', function (): void {
    [$user, $vendor] = completeVendorDraft();

    $this->actingAs($user)
        ->from(route('vendor.onboarding.show'))
        ->post(route('vendor.onboarding.submit', $vendor), ['submission_version' => 2])
        ->assertRedirect(route('vendor.onboarding.show'))
        ->assertSessionHasErrors('submission_version');

    expect($vendor->fresh()->status)->toBe(VendorStatus::Draft)
        ->and(VendorSubmissionSnapshot::query()->count())->toBe(0);
});

test('a vendor manager cannot submit a vendor for approval', function (): void {
    [$owner, $vendor] = completeVendorDraft();
    $manager = User::factory()->create(['status' => UserStatus::Active]);

    VendorMembership::query()->create([
        'vendor_id' => $vendor->id,
        'user_id' => $manager->id,
        'role' => 'vendor_manager',
        'status' => 'active',
    ]);

    $this->actingAs($manager)
        ->post(route('vendor.onboarding.submit', $vendor), ['submission_version' => 1])
        ->assertForbidden();

    expect($vendor->fresh()->status)->toBe(VendorStatus::Draft)
        ->and(VendorSubmissionSnapshot::query()->count())->toBe(0);
});

test('replaying a submission returns the current pending approval state without duplicate facts', function (): void {
    [$user, $vendor] = completeVendorDraft();

    $this->actingAs($user)
        ->post(route('vendor.onboarding.submit', $vendor), ['submission_version' => 1])
        ->assertRedirect(route('vendor.onboarding.show'));

    $this->post(route('vendor.onboarding.submit', $vendor), ['submission_version' => 1])
        ->assertRedirect(route('vendor.onboarding.show'));

    expect($vendor->fresh()->status)->toBe(VendorStatus::PendingApproval)
        ->and(VendorSubmissionSnapshot::query()->count())->toBe(1)
        ->and(VendorStatusHistory::query()->where('to_status', VendorStatus::PendingApproval->value)->count())->toBe(1);
});

test('a rejected vendor can submit fresh versioned evidence and receive approval', function (): void {
    app(DatabaseSeeder::class)->run();

    [$owner, $vendor] = completeVendorDraft();
    $vendor->update(['status' => VendorStatus::Rejected]);
    VendorStatusHistory::query()->create([
        'vendor_id' => $vendor->id,
        'sequence' => 2,
        'from_status' => VendorStatus::PendingApproval->value,
        'to_status' => VendorStatus::Rejected->value,
        'reason_code' => 'document_verification_required',
        'reason_message' => 'Upload updated verification documents before resubmitting.',
        'transitioned_at' => now(),
    ]);

    $this->actingAs($owner)
        ->post(route('vendor.onboarding.resubmission.prepare', $vendor), ['submission_version' => 1])
        ->assertRedirect(route('vendor.onboarding.show'));

    $this->post(route('vendor.onboarding.bank-accounts.store', $vendor), [
        'account_holder_name' => 'Acme Sports Private Limited',
        'bank_name' => 'Replacement Bank',
        'account_number' => '9876543210123456',
        'routing_code' => 'EXAM0000123',
    ])->assertRedirect(route('vendor.onboarding.show'));

    $newDocumentIds = [];

    foreach (['identity_proof', 'business_registration'] as $documentType) {
        $file = File::query()->create([
            'purpose' => FilePurpose::VendorKycDocument,
            'status' => FileStatus::Ready,
            'created_by_user_id' => $owner->id,
            'vendor_id' => $vendor->id,
            'logical_disk' => 'private_files',
            'object_key' => "vendor_kyc_document/2026/08/resubmission-{$documentType}/source",
            'detected_mime_type' => 'image/jpeg',
            'canonical_extension' => 'jpg',
            'size_bytes' => 1024,
            'checksum_sha256' => hash('sha256', "resubmission-{$documentType}"),
            'uploaded_at' => now(),
            'scanned_at' => now(),
            'ready_at' => now(),
        ]);
        $newDocumentIds[] = VendorDocument::query()->create([
            'vendor_id' => $vendor->id,
            'file_id' => $file->id,
            'document_type' => $documentType,
            'submission_version' => 2,
            'status' => 'active',
        ])->id;
    }

    $this->post(route('vendor.onboarding.submit', $vendor), ['submission_version' => 2])
        ->assertRedirect(route('vendor.onboarding.show'));

    $reviewer = User::factory()->create(['status' => UserStatus::Active]);
    $reviewer->roles()->attach(Role::query()->where('code', 'admin_operations')->firstOrFail());

    $this->actingAs($reviewer)
        ->post(route('admin.vendor_reviews.approve', $vendor), ['submission_version' => 2])
        ->assertRedirect(route('admin.vendor_reviews.index'));

    $snapshot = VendorSubmissionSnapshot::query()
        ->where('vendor_id', $vendor->id)
        ->where('submission_version', 2)
        ->sole();

    expect($vendor->fresh()->status)->toBe(VendorStatus::Approved)
        ->and(array_values($snapshot->snapshot['document_ids']))->toEqualCanonicalizing($newDocumentIds)
        ->and($snapshot->snapshot['bank_account_id'])->toBe(
            VendorBankAccount::query()
                ->where('vendor_id', $vendor->id)
                ->where('submission_version', 2)
                ->sole()
                ->id,
        )
        ->and(VendorDocument::query()->where('vendor_id', $vendor->id)->where('submission_version', 1)->count())->toBe(2)
        ->and(VendorStatusHistory::query()->where('vendor_id', $vendor->id)->where('to_status', VendorStatus::Approved->value)->count())->toBe(1);
});

/**
 * @return array{User, Vendor}
 */
function completeVendorDraft(): array
{
    $user = User::factory()->create(['status' => UserStatus::Active]);
    $vendor = Vendor::factory()->create([
        'legal_name' => 'Acme Sports Private Limited',
        'display_name' => 'Acme Sports Arena',
        'legal_entity_type' => 'private_limited_company',
        'primary_contact_name' => 'Riya Sharma',
        'primary_contact_email' => 'owner@example.test',
        'primary_contact_mobile_number' => '+919900001111',
        'is_gst_registered' => false,
    ]);

    VendorMembership::query()->create([
        'vendor_id' => $vendor->id,
        'user_id' => $user->id,
        'role' => 'vendor_owner',
        'status' => 'active',
    ]);

    VendorStatusHistory::query()->create([
        'vendor_id' => $vendor->id,
        'actor_user_id' => $user->id,
        'sequence' => 1,
        'to_status' => VendorStatus::Draft->value,
        'transitioned_at' => now(),
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

    expect($account->id)->toBeInt();

    foreach (['identity_proof', 'business_registration'] as $documentType) {
        $file = File::query()->create([
            'purpose' => FilePurpose::VendorKycDocument,
            'status' => FileStatus::Ready,
            'created_by_user_id' => $user->id,
            'vendor_id' => $vendor->id,
            'logical_disk' => 'private_files',
            'object_key' => "vendor_kyc_document/2026/08/{$documentType}/source",
            'detected_mime_type' => 'image/jpeg',
            'canonical_extension' => 'jpg',
            'size_bytes' => 1024,
            'checksum_sha256' => hash('sha256', $documentType),
            'uploaded_at' => now(),
            'scanned_at' => now(),
            'ready_at' => now(),
        ]);

        VendorDocument::query()->create([
            'vendor_id' => $vendor->id,
            'file_id' => $file->id,
            'document_type' => $documentType,
            'submission_version' => 1,
            'status' => 'active',
        ]);
    }

    return [$user, $vendor];
}
