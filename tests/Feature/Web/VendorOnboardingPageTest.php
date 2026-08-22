<?php

use App\Domain\Files\Enums\FilePurpose;
use App\Domain\Files\Enums\FileStatus;
use App\Domain\Users\Enums\UserStatus;
use App\Domain\Vendors\Enums\VendorStatus;
use App\Models\File;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorBankAccount;
use App\Models\VendorDocument;
use App\Models\VendorMembership;
use App\Models\VendorStatusHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('the vendor onboarding page redirects guests to login', function (): void {
    $this->get(route('vendor.onboarding.show'))
        ->assertRedirect(route('login'));
});

test('an authenticated user can start vendor onboarding and create a draft vendor with an owner membership', function (): void {
    $user = User::factory()->create([
        'name' => 'Riya Sharma',
        'mobile_number' => '+919900001111',
        'email' => 'riya@example.test',
        'status' => UserStatus::Active,
    ]);

    $this->actingAs($user)
        ->get(route('vendor.onboarding.show'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('vendor/Onboarding')
            ->where('vendor.status', VendorStatus::Draft->value)
            ->where('vendor.legal_name', null)
            ->where('vendor.display_name', null)
            ->where('vendor.legal_entity_type', null)
            ->where('vendor.primary_contact_name', null)
            ->where('vendor.primary_contact_email', null)
            ->where('vendor.primary_contact_mobile_number', null)
            ->where('vendor.is_gst_registered', null)
            ->where('vendor.gstin', null)
            ->where('vendor.submission_version', 1)
            ->where('owner.name', 'Riya Sharma')
            ->where('owner.mobile_number', '+919900001111')
            ->where('owner.email', 'riya@example.test'),
        );

    $vendor = Vendor::query()->sole();
    $membership = VendorMembership::query()->sole();
    $history = VendorStatusHistory::query()->sole();

    expect($vendor->status)->toBe(VendorStatus::Draft)
        ->and($vendor->submission_version)->toBe(1)
        ->and($membership->vendor_id)->toBe($vendor->id)
        ->and($membership->user_id)->toBe($user->id)
        ->and($membership->role)->toBe('vendor_owner')
        ->and($history->vendor_id)->toBe($vendor->id)
        ->and($history->actor_user_id)->toBe($user->id)
        ->and($history->sequence)->toBe(1)
        ->and($history->to_status)->toBe(VendorStatus::Draft->value);
});

test('an authenticated owner reuses the same vendor draft when reopening vendor onboarding', function (): void {
    $user = User::factory()->create([
        'status' => UserStatus::Active,
    ]);
    $vendor = Vendor::factory()->create([
        'submission_version' => 4,
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

    $this->actingAs($user)
        ->get(route('vendor.onboarding.show'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('vendor/Onboarding')
            ->where('vendor.id', $vendor->id)
            ->where('vendor.submission_version', 4),
        );

    expect(Vendor::query()->count())->toBe(1)
        ->and(VendorMembership::query()->count())->toBe(1)
        ->and(VendorStatusHistory::query()->count())->toBe(1);
});

test('an active vendor owner can save business details for a draft', function (): void {
    $user = User::factory()->create([
        'status' => UserStatus::Active,
    ]);
    $vendor = Vendor::factory()->create();

    VendorMembership::query()->create([
        'vendor_id' => $vendor->id,
        'user_id' => $user->id,
        'role' => 'vendor_owner',
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->put(route('vendor.onboarding.business-details.update', $vendor), [
            'legal_name' => '  Acme Sports Private Limited  ',
            'display_name' => '  Acme Sports Arena  ',
            'legal_entity_type' => '  private_limited_company  ',
        ])
        ->assertRedirect(route('vendor.onboarding.show'));

    $this->assertDatabaseHas('vendors', [
        'id' => $vendor->id,
        'legal_name' => 'Acme Sports Private Limited',
        'display_name' => 'Acme Sports Arena',
        'legal_entity_type' => 'private_limited_company',
    ]);
});

test('vendor business details require all legal fields', function (): void {
    $user = User::factory()->create([
        'status' => UserStatus::Active,
    ]);
    $vendor = Vendor::factory()->create();

    VendorMembership::query()->create([
        'vendor_id' => $vendor->id,
        'user_id' => $user->id,
        'role' => 'vendor_owner',
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->from(route('vendor.onboarding.show'))
        ->put(route('vendor.onboarding.business-details.update', $vendor), [])
        ->assertRedirect(route('vendor.onboarding.show'))
        ->assertSessionHasErrors([
            'legal_name',
            'display_name',
            'legal_entity_type',
        ]);
});

test('a vendor owner cannot update another vendor business details', function (): void {
    $user = User::factory()->create([
        'status' => UserStatus::Active,
    ]);
    $vendor = Vendor::factory()->create();

    $this->actingAs($user)
        ->put(route('vendor.onboarding.business-details.update', $vendor), [
            'legal_name' => 'Acme Sports Private Limited',
            'display_name' => 'Acme Sports Arena',
            'legal_entity_type' => 'private_limited_company',
        ])
        ->assertForbidden();
});

test('vendor business details cannot be edited after the draft state', function (): void {
    $user = User::factory()->create([
        'status' => UserStatus::Active,
    ]);
    $vendor = Vendor::factory()->approved()->create();

    VendorMembership::query()->create([
        'vendor_id' => $vendor->id,
        'user_id' => $user->id,
        'role' => 'vendor_owner',
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->from(route('vendor.onboarding.show'))
        ->put(route('vendor.onboarding.business-details.update', $vendor), [
            'legal_name' => 'Acme Sports Private Limited',
            'display_name' => 'Acme Sports Arena',
            'legal_entity_type' => 'private_limited_company',
        ])
        ->assertRedirect(route('vendor.onboarding.show'))
        ->assertSessionHasErrors('vendor');

    expect($vendor->fresh()->legal_name)->toBeNull();
});

test('an active vendor owner can save primary contact details for a draft', function (): void {
    $user = User::factory()->create([
        'status' => UserStatus::Active,
    ]);
    $vendor = Vendor::factory()->create();

    VendorMembership::query()->create([
        'vendor_id' => $vendor->id,
        'user_id' => $user->id,
        'role' => 'vendor_owner',
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->put(route('vendor.onboarding.primary-contact.update', $vendor), [
            'primary_contact_name' => '  Riya Sharma  ',
            'primary_contact_email' => '  RIYA@EXAMPLE.TEST  ',
            'primary_contact_mobile_number' => '+919900001111',
        ])
        ->assertRedirect(route('vendor.onboarding.show'));

    $this->assertDatabaseHas('vendors', [
        'id' => $vendor->id,
        'primary_contact_name' => 'Riya Sharma',
        'primary_contact_email' => 'riya@example.test',
        'primary_contact_mobile_number' => '+919900001111',
    ]);
});

test('vendor primary contact details require a valid email and E.164 mobile number', function (): void {
    $user = User::factory()->create([
        'status' => UserStatus::Active,
    ]);
    $vendor = Vendor::factory()->create();

    VendorMembership::query()->create([
        'vendor_id' => $vendor->id,
        'user_id' => $user->id,
        'role' => 'vendor_owner',
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->from(route('vendor.onboarding.show'))
        ->put(route('vendor.onboarding.primary-contact.update', $vendor), [
            'primary_contact_name' => 'Riya Sharma',
            'primary_contact_email' => 'not-an-email',
            'primary_contact_mobile_number' => '9900001111',
        ])
        ->assertRedirect(route('vendor.onboarding.show'))
        ->assertSessionHasErrors([
            'primary_contact_email',
            'primary_contact_mobile_number',
        ]);
});

test('a user cannot update another vendor primary contact', function (): void {
    $user = User::factory()->create([
        'status' => UserStatus::Active,
    ]);
    $vendor = Vendor::factory()->create();

    $this->actingAs($user)
        ->put(route('vendor.onboarding.primary-contact.update', $vendor), [
            'primary_contact_name' => 'Riya Sharma',
            'primary_contact_email' => 'riya@example.test',
            'primary_contact_mobile_number' => '+919900001111',
        ])
        ->assertForbidden();
});

test('vendor primary contact details cannot be edited after the draft state', function (): void {
    $user = User::factory()->create([
        'status' => UserStatus::Active,
    ]);
    $vendor = Vendor::factory()->approved()->create();

    VendorMembership::query()->create([
        'vendor_id' => $vendor->id,
        'user_id' => $user->id,
        'role' => 'vendor_owner',
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->from(route('vendor.onboarding.show'))
        ->put(route('vendor.onboarding.primary-contact.update', $vendor), [
            'primary_contact_name' => 'Riya Sharma',
            'primary_contact_email' => 'riya@example.test',
            'primary_contact_mobile_number' => '+919900001111',
        ])
        ->assertRedirect(route('vendor.onboarding.show'))
        ->assertSessionHasErrors('vendor');

    expect($vendor->fresh()->primary_contact_email)->toBeNull();
});

test('an active vendor owner can save registered GST details for a draft', function (): void {
    $user = User::factory()->create([
        'status' => UserStatus::Active,
    ]);
    $vendor = Vendor::factory()->create();

    VendorMembership::query()->create([
        'vendor_id' => $vendor->id,
        'user_id' => $user->id,
        'role' => 'vendor_owner',
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->put(route('vendor.onboarding.gst-details.update', $vendor), [
            'is_gst_registered' => true,
            'gstin' => '27aabcu9603r1zm',
        ])
        ->assertRedirect(route('vendor.onboarding.show'));

    $this->assertDatabaseHas('vendors', [
        'id' => $vendor->id,
        'is_gst_registered' => true,
        'gstin' => '27AABCU9603R1ZM',
    ]);
});

test('a non-registered vendor cannot supply a GSTIN', function (): void {
    $user = User::factory()->create([
        'status' => UserStatus::Active,
    ]);
    $vendor = Vendor::factory()->create();

    VendorMembership::query()->create([
        'vendor_id' => $vendor->id,
        'user_id' => $user->id,
        'role' => 'vendor_owner',
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->from(route('vendor.onboarding.show'))
        ->put(route('vendor.onboarding.gst-details.update', $vendor), [
            'is_gst_registered' => false,
            'gstin' => '27AABCU9603R1ZM',
        ])
        ->assertRedirect(route('vendor.onboarding.show'))
        ->assertSessionHasErrors('gstin');
});

test('a registered vendor must provide a valid GSTIN', function (): void {
    $user = User::factory()->create([
        'status' => UserStatus::Active,
    ]);
    $vendor = Vendor::factory()->create();

    VendorMembership::query()->create([
        'vendor_id' => $vendor->id,
        'user_id' => $user->id,
        'role' => 'vendor_owner',
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->from(route('vendor.onboarding.show'))
        ->put(route('vendor.onboarding.gst-details.update', $vendor), [
            'is_gst_registered' => true,
            'gstin' => 'invalid',
        ])
        ->assertRedirect(route('vendor.onboarding.show'))
        ->assertSessionHasErrors('gstin');
});

test('a user cannot update another vendor GST details', function (): void {
    $user = User::factory()->create([
        'status' => UserStatus::Active,
    ]);
    $vendor = Vendor::factory()->create();

    $this->actingAs($user)
        ->put(route('vendor.onboarding.gst-details.update', $vendor), [
            'is_gst_registered' => true,
            'gstin' => '27AABCU9603R1ZM',
        ])
        ->assertForbidden();
});

test('vendor GST details cannot be edited after the draft state', function (): void {
    $user = User::factory()->create([
        'status' => UserStatus::Active,
    ]);
    $vendor = Vendor::factory()->approved()->create();

    VendorMembership::query()->create([
        'vendor_id' => $vendor->id,
        'user_id' => $user->id,
        'role' => 'vendor_owner',
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->from(route('vendor.onboarding.show'))
        ->put(route('vendor.onboarding.gst-details.update', $vendor), [
            'is_gst_registered' => true,
            'gstin' => '27AABCU9603R1ZM',
        ])
        ->assertRedirect(route('vendor.onboarding.show'))
        ->assertSessionHasErrors('vendor');

    expect($vendor->fresh()->gstin)->toBeNull();
});

test('the onboarding page exposes the full web ui contract for rejection and resubmission', function (): void {
    $user = User::factory()->create([
        'name' => 'Riya Sharma',
        'mobile_number' => '+919900001111',
        'email' => 'riya@example.test',
        'status' => UserStatus::Active,
    ]);

    $vendor = Vendor::factory()->create([
        'status' => VendorStatus::Rejected,
        'legal_name' => 'Acme Sports Private Limited',
        'display_name' => 'Acme Sports Arena',
        'legal_entity_type' => 'private_limited_company',
        'primary_contact_name' => 'Riya Sharma',
        'primary_contact_email' => 'owner@example.test',
        'primary_contact_mobile_number' => '+919900001111',
        'is_gst_registered' => true,
        'gstin' => '27AABCU9603R1ZM',
        'submission_version' => 3,
    ]);

    VendorMembership::query()->create([
        'vendor_id' => $vendor->id,
        'user_id' => $user->id,
        'role' => 'vendor_owner',
        'status' => 'active',
    ]);

    $file = File::query()->create([
        'purpose' => FilePurpose::VendorKycDocument,
        'status' => FileStatus::Ready,
        'created_by_user_id' => $user->id,
        'vendor_id' => $vendor->id,
        'logical_disk' => 'private_files',
        'object_key' => 'vendor_kyc_document/2026/08/identity-proof/source',
        'detected_mime_type' => 'image/jpeg',
        'canonical_extension' => 'jpg',
        'size_bytes' => 1024,
        'checksum_sha256' => hash('sha256', 'identity-proof'),
        'uploaded_at' => now(),
        'scanned_at' => now(),
        'ready_at' => now(),
    ]);

    VendorDocument::query()->create([
        'vendor_id' => $vendor->id,
        'file_id' => $file->id,
        'document_type' => 'identity_proof',
        'submission_version' => 3,
        'status' => 'active',
    ]);

    VendorBankAccount::query()->create([
        'vendor_id' => $vendor->id,
        'account_holder_name' => 'Acme Sports Private Limited',
        'bank_name' => 'Example Bank',
        'account_number_encrypted' => '1234567890123456',
        'account_number_last_four' => '3456',
        'routing_code_encrypted' => 'EXAM0000123',
        'country_code' => 'IN',
        'currency' => 'INR',
        'submission_version' => 3,
        'status' => 'active',
    ]);

    VendorStatusHistory::query()->create([
        'vendor_id' => $vendor->id,
        'actor_user_id' => $user->id,
        'sequence' => 1,
        'from_status' => VendorStatus::PendingApproval->value,
        'to_status' => VendorStatus::Rejected->value,
        'reason_code' => 'document_verification_required',
        'reason_message' => 'Upload a current business registration document before resubmitting.',
        'transitioned_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('vendor.onboarding.show'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('vendor/Onboarding')
            ->where('vendor.id', $vendor->id)
            ->where('vendor.status', VendorStatus::Rejected->value)
            ->where('vendor.can_edit', true)
            ->where('vendor.submission_version', 3)
            ->where('owner.name', 'Riya Sharma')
            ->has('documentTypes', 3)
            ->where('documentTypes.0.value', 'identity_proof')
            ->where('kycDocuments.0.document_type', 'identity_proof')
            ->where('kycDocuments.0.file_status', FileStatus::Ready->value)
            ->where('bankAccounts.0.bank_name', 'Example Bank')
            ->where('rejection.reason_code', 'document_verification_required')
            ->where(
                'routes.prepare_resubmission',
                route('vendor.onboarding.resubmission.prepare', $vendor),
            )
            ->where(
                'routes.submit',
                route('vendor.onboarding.submit', $vendor),
            ),
        );
});

test('the vendor login page uses vendor specific otp messaging', function (): void {
    $this->get(route('vendor.login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/Login')
            ->where('intendedUrl', route('vendor.onboarding.show'))
            ->where('surfaceTitle', 'Vendor access')
            ->where(
                'surfaceDescription',
                'Use your mobile OTP to continue vendor onboarding, upload compliance evidence, and track review decisions.',
            ),
        );
});
