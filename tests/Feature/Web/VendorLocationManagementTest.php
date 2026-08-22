<?php

use App\Domain\Files\Enums\FilePurpose;
use App\Domain\Files\Enums\FileStatus;
use App\Domain\Locations\Enums\LocationStatus;
use App\Domain\Users\Enums\UserStatus;
use App\Models\Amenity;
use App\Models\File;
use App\Models\Location;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('a vendor owner can view the locations index for their managed vendor', function (): void {
    [$user, $vendor] = vendorManager('vendor_owner');
    $location = Location::query()->create(locationAttributes($vendor, [
        'name' => 'Indiranagar Arena',
        'city' => 'Bengaluru',
        'state' => 'Karnataka',
    ]));

    $location->operatingHours()->create([
        'weekday' => 1,
        'sequence' => 1,
        'opens_at_time' => '06:00:00',
        'closes_at_time' => '22:00:00',
        'ends_next_day' => false,
    ]);

    $this->actingAs($user)
        ->get(route('vendor.locations.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('vendor/locations/Index')
            ->where('vendor.id', $vendor->id)
            ->where('locations.0.id', $location->id)
            ->where('locations.0.name', 'Indiranagar Arena')
            ->where('locations.0.status', LocationStatus::Active->value),
        );
});

test('location create and edit pages expose the selectable gallery image library', function (): void {
    [$user, $vendor] = vendorManager('vendor_owner');
    $availableFile = readyLocationImage($vendor, $user, 'gallery/available.jpg');
    $currentFile = readyLocationImage($vendor, $user, 'gallery/current.jpg');
    $usedElsewhereFile = readyLocationImage($vendor, $user, 'gallery/other-location.jpg');
    $otherLocation = Location::query()->create(locationAttributes($vendor, [
        'name' => 'Other Location',
    ]));
    $editableLocation = Location::query()->create(locationAttributes($vendor, [
        'name' => 'Editable Location',
    ]));

    $otherLocation->images()->create([
        'file_id' => $usedElsewhereFile->id,
        'sort_order' => 1,
        'caption' => 'Other location image',
        'alt_text' => 'Other location image alt',
    ]);
    $editableLocation->images()->create([
        'file_id' => $currentFile->id,
        'sort_order' => 1,
        'caption' => 'Current image',
        'alt_text' => 'Current image alt',
    ]);

    $this->actingAs($user)
        ->get(route('vendor.locations.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('vendor/locations/Form')
            ->where('mode', 'create')
            ->has('available_images', 1)
            ->where('available_images.0.id', $availableFile->id),
        );

    $this->actingAs($user)
        ->get(route('vendor.locations.edit', $editableLocation))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('vendor/locations/Form')
            ->where('mode', 'edit')
            ->has('available_images', 2)
            ->where('available_images.0.id', $availableFile->id)
            ->where('available_images.1.id', $currentFile->id)
            ->where('available_images.1.attached_to_current_location', true),
        );
});

test('a vendor owner can create a location with hours amenities and images', function (): void {
    [$user, $vendor] = vendorManager('vendor_owner');
    $amenities = Amenity::query()->insertGetId([
        'name' => 'Parking',
        'code' => 'parking',
        'description' => 'Covered parking',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $imageFile = readyLocationImage($vendor, $user);

    $this->actingAs($user)
        ->post(route('vendor.locations.store'), [
            'name' => '  Whitefield Play Zone  ',
            'address_line_1' => '  90 Main Road  ',
            'address_line_2' => '  Level 2  ',
            'landmark' => '  Near Metro  ',
            'locality' => '  Whitefield  ',
            'city' => '  Bengaluru  ',
            'state' => '  Karnataka  ',
            'postal_code' => ' 560066 ',
            'country_code' => 'in',
            'latitude' => '12.969800',
            'longitude' => '77.749900',
            'timezone' => 'Asia/Kolkata',
            'amenity_ids' => [$amenities],
            'operating_hours' => [
                [
                    'weekday' => 1,
                    'opens_at_time' => '06:00',
                    'closes_at_time' => '11:00',
                    'ends_next_day' => false,
                ],
                [
                    'weekday' => 1,
                    'opens_at_time' => '17:00',
                    'closes_at_time' => '22:00',
                    'ends_next_day' => false,
                ],
                [
                    'weekday' => 5,
                    'opens_at_time' => '22:00',
                    'closes_at_time' => '02:00',
                    'ends_next_day' => true,
                ],
            ],
            'images' => [
                [
                    'file_id' => $imageFile->id,
                    'caption' => '  Main entrance  ',
                    'alt_text' => '  Venue entrance at night  ',
                ],
            ],
        ])
        ->assertRedirect(route('vendor.locations.index'));

    $location = Location::query()->sole();

    expect($location->vendor_id)->toBe($vendor->id)
        ->and($location->name)->toBe('Whitefield Play Zone')
        ->and($location->address_line_1)->toBe('90 Main Road')
        ->and($location->address_line_2)->toBe('Level 2')
        ->and($location->landmark)->toBe('Near Metro')
        ->and($location->locality)->toBe('Whitefield')
        ->and($location->city)->toBe('Bengaluru')
        ->and($location->state)->toBe('Karnataka')
        ->and($location->postal_code)->toBe('560066')
        ->and($location->country_code)->toBe('IN')
        ->and($location->status)->toBe(LocationStatus::Active);

    expect($location->operatingHours()->count())->toBe(3)
        ->and($location->amenities()->pluck('amenities.id')->all())->toBe([$amenities])
        ->and($location->images()->sole()->file_id)->toBe($imageFile->id)
        ->and($location->images()->sole()->caption)->toBe('Main entrance')
        ->and($location->images()->sole()->alt_text)->toBe('Venue entrance at night');
});

test('a vendor manager can update a location and replace related records', function (): void {
    [$user, $vendor] = vendorManager('vendor_manager');
    $location = Location::query()->create(locationAttributes($vendor, [
        'name' => 'Old Arena',
    ]));
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
    $oldImage = readyLocationImage($vendor, $user);
    $newImage = readyLocationImage($vendor, $user, 'gallery/new-image.jpg');

    $location->operatingHours()->create([
        'weekday' => 1,
        'sequence' => 1,
        'opens_at_time' => '06:00:00',
        'closes_at_time' => '18:00:00',
        'ends_next_day' => false,
    ]);
    $location->amenities()->attach($oldAmenity->id);
    $location->images()->create([
        'file_id' => $oldImage->id,
        'sort_order' => 1,
        'caption' => 'Legacy image',
        'alt_text' => 'Legacy alt text',
    ]);

    $this->actingAs($user)
        ->put(route('vendor.locations.update', $location), [
            'name' => 'Updated Arena',
            'address_line_1' => '12 New Street',
            'address_line_2' => '',
            'landmark' => '',
            'locality' => 'CBD',
            'city' => 'Mysuru',
            'state' => 'Karnataka',
            'postal_code' => '570001',
            'country_code' => 'IN',
            'latitude' => '',
            'longitude' => '',
            'timezone' => 'Asia/Kolkata',
            'amenity_ids' => [$newAmenity->id],
            'operating_hours' => [
                [
                    'weekday' => 2,
                    'opens_at_time' => '07:00',
                    'closes_at_time' => '21:00',
                    'ends_next_day' => false,
                ],
            ],
            'images' => [
                [
                    'file_id' => $newImage->id,
                    'caption' => 'Updated image',
                    'alt_text' => 'Updated alt text',
                ],
            ],
        ])
        ->assertRedirect(route('vendor.locations.edit', $location));

    $location->refresh();

    expect($location->name)->toBe('Updated Arena')
        ->and($location->city)->toBe('Mysuru')
        ->and($location->latitude)->toBeNull()
        ->and($location->longitude)->toBeNull()
        ->and($location->amenities()->pluck('amenities.id')->all())->toBe([$newAmenity->id])
        ->and($location->operatingHours()->sole()->weekday)->toBe(2)
        ->and($location->images()->sole()->file_id)->toBe($newImage->id);
});

test('a vendor owner can activate and deactivate a location', function (): void {
    [$user, $vendor] = vendorManager('vendor_owner');
    $location = Location::query()->create(locationAttributes($vendor, [
        'status' => LocationStatus::Inactive->value,
    ]));

    $this->actingAs($user)
        ->post(route('vendor.locations.status.update', $location), [
            'status' => LocationStatus::Active->value,
        ])
        ->assertRedirect(route('vendor.locations.edit', $location));

    expect($location->fresh()->status)->toBe(LocationStatus::Active);

    $this->actingAs($user)
        ->post(route('vendor.locations.status.update', $location), [
            'status' => LocationStatus::Inactive->value,
        ])
        ->assertRedirect(route('vendor.locations.edit', $location));

    expect($location->fresh()->status)->toBe(LocationStatus::Inactive);
});

test('location creation requires paired coordinates and non-overlapping operating hours', function (): void {
    [$user] = vendorManager('vendor_owner');

    $this->actingAs($user)
        ->from(route('vendor.locations.create'))
        ->post(route('vendor.locations.store'), [
            'name' => 'Broken Arena',
            'address_line_1' => '13 Arena Road',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'postal_code' => '560001',
            'country_code' => 'IN',
            'latitude' => '12.971599',
            'timezone' => 'Asia/Kolkata',
            'operating_hours' => [
                [
                    'weekday' => 1,
                    'opens_at_time' => '09:00',
                    'closes_at_time' => '12:00',
                    'ends_next_day' => false,
                ],
                [
                    'weekday' => 1,
                    'opens_at_time' => '11:30',
                    'closes_at_time' => '14:00',
                    'ends_next_day' => false,
                ],
            ],
        ])
        ->assertRedirect(route('vendor.locations.create'))
        ->assertSessionHasErrors([
            'latitude',
            'longitude',
            'operating_hours.1.opens_at_time',
        ]);

    expect(Location::query()->count())->toBe(0);
});

test('a vendor cannot update another vendors location', function (): void {
    [$user] = vendorManager('vendor_owner');
    $otherVendor = Vendor::factory()->create();
    $location = Location::query()->create(locationAttributes($otherVendor));

    $this->actingAs($user)
        ->put(route('vendor.locations.update', $location), [
            'name' => 'Cross-tenant update',
            'address_line_1' => '12 Arena Road',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'postal_code' => '560001',
            'country_code' => 'IN',
            'timezone' => 'Asia/Kolkata',
        ])
        ->assertForbidden();
});

test('vendor staff without manager access cannot open the locations workflow', function (): void {
    [$user] = vendorManager('vendor_staff');

    $this->actingAs($user)
        ->get(route('vendor.locations.index'))
        ->assertForbidden();
});

test('a location image must belong to the same vendor and be ready', function (): void {
    [$user, $vendor] = vendorManager('vendor_owner');
    $otherVendor = Vendor::factory()->create();
    $foreignFile = readyLocationImage($otherVendor, $user);

    $this->actingAs($user)
        ->from(route('vendor.locations.create'))
        ->post(route('vendor.locations.store'), [
            'name' => 'Invalid Image Arena',
            'address_line_1' => '12 Arena Road',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'postal_code' => '560001',
            'country_code' => 'IN',
            'timezone' => 'Asia/Kolkata',
            'images' => [
                [
                    'file_id' => $foreignFile->id,
                ],
            ],
        ])
        ->assertRedirect(route('vendor.locations.create'))
        ->assertSessionHasErrors('images.0.file_id');

    expect(Location::query()->count())->toBe(0);
});

/**
 * @return array{User, Vendor}
 */
function vendorManager(string $role): array
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
function locationAttributes(Vendor $vendor, array $overrides = []): array
{
    return array_merge([
        'vendor_id' => $vendor->id,
        'name' => 'Koramangala Sports Hub',
        'address_line_1' => '44 5th Block',
        'address_line_2' => null,
        'landmark' => null,
        'locality' => null,
        'city' => 'Bengaluru',
        'state' => 'Karnataka',
        'postal_code' => '560095',
        'country_code' => 'IN',
        'latitude' => null,
        'longitude' => null,
        'timezone' => 'Asia/Kolkata',
        'status' => LocationStatus::Active->value,
    ], $overrides);
}

function readyLocationImage(Vendor $vendor, User $user, string $objectKey = 'gallery/main.jpg'): File
{
    return File::query()->create([
        'purpose' => FilePurpose::LocationImage,
        'status' => FileStatus::Ready,
        'created_by_user_id' => $user->id,
        'vendor_id' => $vendor->id,
        'logical_disk' => 'private_files',
        'object_key' => $objectKey,
        'original_name' => 'main.jpg',
        'size_bytes' => 1024,
        'checksum_sha256' => hash('sha256', $objectKey),
        'uploaded_at' => now(),
        'ready_at' => now(),
    ]);
}
