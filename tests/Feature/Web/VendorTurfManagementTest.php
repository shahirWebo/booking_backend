<?php

use App\Domain\Files\Enums\FilePurpose;
use App\Domain\Files\Enums\FileStatus;
use App\Domain\Locations\Enums\LocationStatus;
use App\Domain\Turfs\Enums\TurfStatus;
use App\Domain\Users\Enums\UserStatus;
use App\Models\Amenity;
use App\Models\File;
use App\Models\Location;
use App\Models\Sport;
use App\Models\Turf;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('a vendor owner can view the turf index for a managed location', function (): void {
    [$user, $vendor] = turfVendorManager('vendor_owner');
    $location = Location::query()->create(turfLocationAttributes($vendor, [
        'name' => 'Indiranagar Arena',
    ]));
    $turf = Turf::query()->create([
        'location_id' => $location->id,
        'name' => 'Five-a-side',
        'status' => TurfStatus::Active,
        'surface_type' => 'artificial_grass',
        'is_indoor' => false,
        'capacity_count' => 10,
        'length_meters' => 40,
        'width_meters' => 20,
    ]);

    $this->actingAs($user)
        ->get(route('vendor.locations.turfs.index', $location))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('vendor/turfs/Index')
            ->where('location.id', $location->id)
            ->where('location.name', 'Indiranagar Arena')
            ->where('turfs.0.id', $turf->id)
            ->where('turfs.0.name', 'Five-a-side')
            ->where('turfs.0.status', TurfStatus::Active->value),
        );
});

test('turf create and edit pages expose selectable turf gallery files', function (): void {
    [$user, $vendor] = turfVendorManager('vendor_owner');
    $location = Location::query()->create(turfLocationAttributes($vendor, [
        'name' => 'Whitefield Sports Hub',
    ]));
    $availableFile = readyTurfImage($vendor, $user, 'turfs/available.jpg');
    $currentFile = readyTurfImage($vendor, $user, 'turfs/current.jpg');
    $usedElsewhereFile = readyTurfImage($vendor, $user, 'turfs/used-elsewhere.jpg');
    $otherTurf = Turf::query()->create([
        'location_id' => $location->id,
        'name' => 'Other Turf',
        'status' => TurfStatus::Inactive,
    ]);
    $editableTurf = Turf::query()->create([
        'location_id' => $location->id,
        'name' => 'Editable Turf',
        'status' => TurfStatus::Active,
    ]);

    $otherTurf->images()->create([
        'file_id' => $usedElsewhereFile->id,
        'sort_order' => 1,
        'caption' => 'Other turf image',
        'alt_text' => 'Other turf image alt',
    ]);
    $editableTurf->images()->create([
        'file_id' => $currentFile->id,
        'sort_order' => 1,
        'caption' => 'Current turf image',
        'alt_text' => 'Current turf image alt',
    ]);

    $this->actingAs($user)
        ->get(route('vendor.locations.turfs.create', $location))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('vendor/turfs/Form')
            ->where('mode', 'create')
            ->has('available_images', 1)
            ->where('available_images.0.id', $availableFile->id),
        );

    $this->actingAs($user)
        ->get(route('vendor.turfs.edit', $editableTurf))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('vendor/turfs/Form')
            ->where('mode', 'edit')
            ->has('available_images', 2)
            ->where('available_images.0.id', $availableFile->id)
            ->where('available_images.1.id', $currentFile->id)
            ->where('available_images.1.attached_to_current_turf', true),
        );
});

test('a vendor owner can create a turf with sports amenities images rules and metadata', function (): void {
    [$user, $vendor] = turfVendorManager('vendor_owner');
    $location = Location::query()->create(turfLocationAttributes($vendor, [
        'name' => 'Koramangala Sports Hub',
    ]));
    $sport = Sport::query()->create([
        'name' => 'Football',
        'code' => 'football',
        'description' => 'Five a side football',
        'is_active' => true,
    ]);
    $amenity = Amenity::query()->create([
        'name' => 'Floodlights',
        'code' => 'floodlights',
        'description' => 'Night lighting',
        'is_active' => true,
    ]);
    $imageFile = readyTurfImage($vendor, $user);

    $this->actingAs($user)
        ->post(route('vendor.locations.turfs.store', $location), [
            'name' => '  Centre Pitch  ',
            'description' => '  Premier five-a-side arena  ',
            'surface_type' => '  artificial_grass  ',
            'is_indoor' => false,
            'capacity_count' => 10,
            'length_meters' => '40.00',
            'width_meters' => '20.00',
            'sport_ids' => [$sport->id],
            'amenity_ids' => [$amenity->id],
            'images' => [
                [
                    'file_id' => $imageFile->id,
                    'caption' => '  Main angle  ',
                    'alt_text' => '  Turf view from entry gate  ',
                ],
            ],
            'rules' => [
                [
                    'title' => '  No metal studs  ',
                    'description' => '  Wear rubber studs only.  ',
                    'is_active' => true,
                ],
            ],
        ])
        ->assertRedirect(route('vendor.locations.turfs.index', $location));

    $turf = Turf::query()->sole();

    expect($turf->location_id)->toBe($location->id)
        ->and($turf->name)->toBe('Centre Pitch')
        ->and($turf->description)->toBe('Premier five-a-side arena')
        ->and($turf->surface_type)->toBe('artificial_grass')
        ->and($turf->is_indoor)->toBeFalse()
        ->and($turf->capacity_count)->toBe(10)
        ->and($turf->length_meters)->toBe(40.0)
        ->and($turf->width_meters)->toBe(20.0)
        ->and($turf->status)->toBe(TurfStatus::Inactive);

    expect($turf->sports()->pluck('sports.id')->all())->toBe([$sport->id])
        ->and($turf->amenities()->pluck('amenities.id')->all())->toBe([$amenity->id])
        ->and($turf->images()->sole()->file_id)->toBe($imageFile->id)
        ->and($turf->images()->sole()->caption)->toBe('Main angle')
        ->and($turf->rules()->sole()->title)->toBe('No metal studs')
        ->and($turf->rules()->sole()->description)->toBe('Wear rubber studs only.');
});

test('a vendor manager can update a turf and replace related records', function (): void {
    [$user, $vendor] = turfVendorManager('vendor_manager');
    $location = Location::query()->create(turfLocationAttributes($vendor));
    $turf = Turf::query()->create([
        'location_id' => $location->id,
        'name' => 'Legacy Pitch',
        'status' => TurfStatus::Inactive,
        'surface_type' => 'concrete',
        'capacity_count' => 8,
    ]);
    $oldSport = Sport::query()->create([
        'name' => 'Football',
        'code' => 'football',
        'description' => null,
        'is_active' => true,
    ]);
    $newSport = Sport::query()->create([
        'name' => 'Box Cricket',
        'code' => 'box-cricket',
        'description' => null,
        'is_active' => true,
    ]);
    $oldAmenity = Amenity::query()->create([
        'name' => 'Parking',
        'code' => 'parking',
        'description' => null,
        'is_active' => true,
    ]);
    $newAmenity = Amenity::query()->create([
        'name' => 'Cafe',
        'code' => 'cafe',
        'description' => null,
        'is_active' => true,
    ]);
    $oldImage = readyTurfImage($vendor, $user, 'turfs/old.jpg');
    $newImage = readyTurfImage($vendor, $user, 'turfs/new.jpg');

    $turf->sports()->attach($oldSport->id);
    $turf->amenities()->attach($oldAmenity->id);
    $turf->images()->create([
        'file_id' => $oldImage->id,
        'sort_order' => 1,
        'caption' => 'Legacy image',
        'alt_text' => 'Legacy alt',
    ]);
    $turf->rules()->create([
        'title' => 'Old rule',
        'description' => 'Old description',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->put(route('vendor.turfs.update', $turf), [
            'name' => 'Updated Pitch',
            'description' => '',
            'surface_type' => 'acrylic',
            'is_indoor' => true,
            'capacity_count' => '12',
            'length_meters' => '32.50',
            'width_meters' => '18.25',
            'sport_ids' => [$newSport->id],
            'amenity_ids' => [$newAmenity->id],
            'images' => [
                [
                    'file_id' => $newImage->id,
                    'caption' => 'Updated image',
                    'alt_text' => 'Updated alt',
                ],
            ],
            'rules' => [
                [
                    'title' => 'Bring socks',
                    'description' => 'Non-marking shoes required.',
                    'is_active' => true,
                ],
            ],
        ])
        ->assertRedirect(route('vendor.turfs.edit', $turf));

    $turf->refresh();

    expect($turf->name)->toBe('Updated Pitch')
        ->and($turf->description)->toBeNull()
        ->and($turf->surface_type)->toBe('acrylic')
        ->and($turf->is_indoor)->toBeTrue()
        ->and($turf->capacity_count)->toBe(12)
        ->and($turf->length_meters)->toBe(32.5)
        ->and($turf->width_meters)->toBe(18.25)
        ->and($turf->sports()->pluck('sports.id')->all())->toBe([$newSport->id])
        ->and($turf->amenities()->pluck('amenities.id')->all())->toBe([$newAmenity->id])
        ->and($turf->images()->sole()->file_id)->toBe($newImage->id)
        ->and($turf->rules()->sole()->title)->toBe('Bring socks');
});

test('a vendor owner can activate and deactivate a turf', function (): void {
    [$user, $vendor] = turfVendorManager('vendor_owner');
    $location = Location::query()->create(turfLocationAttributes($vendor));
    $turf = Turf::query()->create([
        'location_id' => $location->id,
        'name' => 'North Court',
        'status' => TurfStatus::Inactive,
    ]);

    $this->actingAs($user)
        ->post(route('vendor.turfs.status.update', $turf), [
            'status' => TurfStatus::Active->value,
        ])
        ->assertRedirect(route('vendor.turfs.edit', $turf));

    expect($turf->fresh()->status)->toBe(TurfStatus::Active);

    $this->actingAs($user)
        ->post(route('vendor.turfs.status.update', $turf), [
            'status' => TurfStatus::Inactive->value,
        ])
        ->assertRedirect(route('vendor.turfs.edit', $turf));

    expect($turf->fresh()->status)->toBe(TurfStatus::Inactive);
});

test('turf creation requires paired dimensions', function (): void {
    [$user, $vendor] = turfVendorManager('vendor_owner');
    $location = Location::query()->create(turfLocationAttributes($vendor));

    $this->actingAs($user)
        ->from(route('vendor.locations.turfs.create', $location))
        ->post(route('vendor.locations.turfs.store', $location), [
            'name' => 'Broken Pitch',
            'surface_type' => 'artificial_grass',
            'length_meters' => '40.00',
        ])
        ->assertRedirect(route('vendor.locations.turfs.create', $location))
        ->assertSessionHasErrors([
            'length_meters',
            'width_meters',
        ]);

    expect(Turf::query()->count())->toBe(0);
});

test('a vendor cannot update another vendors turf', function (): void {
    [$user] = turfVendorManager('vendor_owner');
    $otherVendor = Vendor::factory()->create();
    $location = Location::query()->create(turfLocationAttributes($otherVendor));
    $turf = Turf::query()->create([
        'location_id' => $location->id,
        'name' => 'Foreign Turf',
        'status' => TurfStatus::Inactive,
    ]);

    $this->actingAs($user)
        ->put(route('vendor.turfs.update', $turf), [
            'name' => 'Cross-tenant update',
        ])
        ->assertForbidden();
});

test('vendor staff without manager access cannot open the turf workflow', function (): void {
    [$user, $vendor] = turfVendorManager('vendor_staff');
    $location = Location::query()->create(turfLocationAttributes($vendor));

    $this->actingAs($user)
        ->get(route('vendor.locations.turfs.index', $location))
        ->assertForbidden();
});

test('a turf image must belong to the same vendor and be ready', function (): void {
    [$user, $vendor] = turfVendorManager('vendor_owner');
    $location = Location::query()->create(turfLocationAttributes($vendor));
    $otherVendor = Vendor::factory()->create();
    $foreignFile = readyTurfImage($otherVendor, $user);

    $this->actingAs($user)
        ->from(route('vendor.locations.turfs.create', $location))
        ->post(route('vendor.locations.turfs.store', $location), [
            'name' => 'Invalid Turf',
            'images' => [
                [
                    'file_id' => $foreignFile->id,
                ],
            ],
        ])
        ->assertRedirect(route('vendor.locations.turfs.create', $location))
        ->assertSessionHasErrors('images.0.file_id');

    expect(Turf::query()->count())->toBe(0);
});

/**
 * @return array{User, Vendor}
 */
function turfVendorManager(string $role): array
{
    $user = User::factory()->create([
        'status' => UserStatus::Active,
    ]);
    $vendor = Vendor::factory()->create();

    VendorMembership::query()->create([
        'vendor_id' => $vendor->id,
        'user_id' => $user->id,
        'role' => $role,
        'status' => 'active',
    ]);

    return [$user, $vendor];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function turfLocationAttributes(Vendor $vendor, array $overrides = []): array
{
    return array_merge([
        'vendor_id' => $vendor->id,
        'name' => 'HSR Sports Complex',
        'address_line_1' => '44 Club Road',
        'address_line_2' => null,
        'landmark' => null,
        'locality' => null,
        'city' => 'Bengaluru',
        'state' => 'Karnataka',
        'postal_code' => '560102',
        'country_code' => 'IN',
        'latitude' => null,
        'longitude' => null,
        'timezone' => 'Asia/Kolkata',
        'status' => LocationStatus::Active->value,
    ], $overrides);
}

function readyTurfImage(Vendor $vendor, User $user, string $objectKey = 'turfs/main.jpg'): File
{
    return File::query()->create([
        'purpose' => FilePurpose::TurfImage,
        'status' => FileStatus::Ready,
        'created_by_user_id' => $user->id,
        'vendor_id' => $vendor->id,
        'logical_disk' => 'private_files',
        'object_key' => $objectKey,
        'original_name' => 'main.jpg',
        'size_bytes' => 1024,
        'checksum_sha256' => hash('sha256', $objectKey),
        'uploaded_at' => now(),
        'scanned_at' => now(),
        'ready_at' => now(),
    ]);
}
