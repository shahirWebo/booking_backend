<?php

use App\Domain\Files\Enums\FilePurpose;
use App\Domain\Files\Enums\FileStatus;
use App\Domain\Users\Enums\UserStatus;
use App\Models\File;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDocument;
use App\Models\VendorMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('upload_quarantine');
    Storage::fake('private_files');
});

test('an active vendor owner can upload a KYC document that is scanned and privately attached', function (): void {
    [$user, $vendor] = vendorOwner();

    $this->actingAs($user)
        ->post(route('vendor.onboarding.kyc-documents.store', $vendor), [
            'document_type' => 'identity_proof',
            'document' => UploadedFile::fake()->image('identity.jpg', 200, 300),
        ])
        ->assertRedirect(route('vendor.onboarding.show'));

    $file = File::query()->sole();
    $document = VendorDocument::query()->sole();

    expect($file->purpose)->toBe(FilePurpose::VendorKycDocument)
        ->and($file->status)->toBe(FileStatus::Ready)
        ->and($file->logical_disk)->toBe('private_files')
        ->and($file->object_key)->toStartWith('vendor_kyc_document/')
        ->and($file->object_key)->not->toContain('identity.jpg')
        ->and($file->detected_mime_type)->toBe('image/jpeg')
        ->and($file->checksum_sha256)->toHaveLength(64)
        ->and($document->vendor_id)->toBe($vendor->id)
        ->and($document->file_id)->toBe($file->id)
        ->and($document->document_type)->toBe('identity_proof')
        ->and($document->status)->toBe('active');

    Storage::disk('private_files')->assertExists($file->object_key);
    Storage::disk('upload_quarantine')->assertMissing($file->object_key);
});

test('vendor KYC uploads reject disallowed content before storage', function (): void {
    [$user, $vendor] = vendorOwner();

    $this->actingAs($user)
        ->from(route('vendor.onboarding.show'))
        ->post(route('vendor.onboarding.kyc-documents.store', $vendor), [
            'document_type' => 'identity_proof',
            'document' => UploadedFile::fake()->create('payload.svg', 10, 'image/svg+xml'),
        ])
        ->assertRedirect(route('vendor.onboarding.show'))
        ->assertSessionHasErrors('document');

    expect(File::query()->count())->toBe(0)
        ->and(VendorDocument::query()->count())->toBe(0);
});

test('a user cannot upload a KYC document for another vendor', function (): void {
    $user = User::factory()->create(['status' => UserStatus::Active]);
    $vendor = Vendor::factory()->create();

    $this->actingAs($user)
        ->post(route('vendor.onboarding.kyc-documents.store', $vendor), [
            'document_type' => 'identity_proof',
            'document' => UploadedFile::fake()->image('identity.jpg'),
        ])
        ->assertForbidden();

    expect(File::query()->count())->toBe(0)
        ->and(VendorDocument::query()->count())->toBe(0);
});

test('vendor KYC uploads are locked after the draft state', function (): void {
    [$user, $vendor] = vendorOwner();
    $vendor->update(['status' => 'approved']);

    $this->actingAs($user)
        ->from(route('vendor.onboarding.show'))
        ->post(route('vendor.onboarding.kyc-documents.store', $vendor), [
            'document_type' => 'identity_proof',
            'document' => UploadedFile::fake()->image('identity.jpg'),
        ])
        ->assertRedirect(route('vendor.onboarding.show'))
        ->assertSessionHasErrors('vendor');

    expect(File::query()->count())->toBe(0)
        ->and(VendorDocument::query()->count())->toBe(0);
});

/**
 * @return array{User, Vendor}
 */
function vendorOwner(): array
{
    $user = User::factory()->create(['status' => UserStatus::Active]);
    $vendor = Vendor::factory()->create();

    VendorMembership::query()->create([
        'vendor_id' => $vendor->id,
        'user_id' => $user->id,
        'role' => 'vendor_owner',
        'status' => 'active',
    ]);

    return [$user, $vendor];
}
