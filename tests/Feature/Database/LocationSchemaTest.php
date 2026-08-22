<?php

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('location tables expose vendor ownership, address, coordinate, timezone, and attachment columns', function (): void {
    expect(Schema::hasColumns('locations', [
        'vendor_id',
        'name',
        'address_line_1',
        'address_line_2',
        'landmark',
        'locality',
        'city',
        'state',
        'postal_code',
        'country_code',
        'latitude',
        'longitude',
        'timezone',
        'status',
    ]))->toBeTrue();

    expect(Schema::hasColumns('location_operating_hours', [
        'location_id',
        'weekday',
        'sequence',
        'opens_at_time',
        'closes_at_time',
        'ends_next_day',
    ]))->toBeTrue();

    expect(Schema::hasColumns('location_amenities', [
        'location_id',
        'amenity_id',
    ]))->toBeTrue();

    expect(Schema::hasColumns('location_images', [
        'location_id',
        'file_id',
        'sort_order',
        'caption',
        'alt_text',
    ]))->toBeTrue();
});

test('locations enforce vendor ownership and valid coordinate ranges', function (): void {
    $vendor = Vendor::factory()->create();

    $locationId = DB::table('locations')->insertGetId([
        'vendor_id' => $vendor->id,
        'name' => 'Indiranagar Arena',
        'address_line_1' => '12 Arena Road',
        'city' => 'Bengaluru',
        'state' => 'Karnataka',
        'postal_code' => '560038',
        'country_code' => 'IN',
        'latitude' => '12.971599',
        'longitude' => '77.594566',
        'timezone' => 'Asia/Kolkata',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(DB::table('locations')->where('id', $locationId)->exists())->toBeTrue();

    expect(fn () => DB::table('locations')->insert([
        'vendor_id' => $vendor->id,
        'name' => 'Broken Coordinates Arena',
        'address_line_1' => '13 Arena Road',
        'city' => 'Bengaluru',
        'state' => 'Karnataka',
        'postal_code' => '560038',
        'country_code' => 'IN',
        'latitude' => '91.000000',
        'longitude' => '77.594566',
        'timezone' => 'Asia/Kolkata',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    expect(fn () => DB::table('locations')->insert([
        'vendor_id' => $vendor->id,
        'name' => 'Half Coordinates Arena',
        'address_line_1' => '14 Arena Road',
        'city' => 'Bengaluru',
        'state' => 'Karnataka',
        'postal_code' => '560038',
        'country_code' => 'IN',
        'latitude' => '12.971599',
        'timezone' => 'Asia/Kolkata',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

test('location operating hours support multiple windows per weekday and reject invalid local-time rules', function (): void {
    $vendor = Vendor::factory()->create();
    $locationId = DB::table('locations')->insertGetId([
        'vendor_id' => $vendor->id,
        'name' => 'Koramangala Sports Hub',
        'address_line_1' => '44 5th Block',
        'city' => 'Bengaluru',
        'state' => 'Karnataka',
        'postal_code' => '560095',
        'country_code' => 'IN',
        'timezone' => 'Asia/Kolkata',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('location_operating_hours')->insert([
        [
            'location_id' => $locationId,
            'weekday' => 1,
            'sequence' => 1,
            'opens_at_time' => '06:00:00',
            'closes_at_time' => '11:00:00',
            'ends_next_day' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'location_id' => $locationId,
            'weekday' => 1,
            'sequence' => 2,
            'opens_at_time' => '17:00:00',
            'closes_at_time' => '22:00:00',
            'ends_next_day' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'location_id' => $locationId,
            'weekday' => 5,
            'sequence' => 1,
            'opens_at_time' => '22:00:00',
            'closes_at_time' => '02:00:00',
            'ends_next_day' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    expect(DB::table('location_operating_hours')->where('location_id', $locationId)->count())->toBe(3);

    expect(fn () => DB::table('location_operating_hours')->insert([
        'location_id' => $locationId,
        'weekday' => 0,
        'sequence' => 1,
        'opens_at_time' => '09:00:00',
        'closes_at_time' => '12:00:00',
        'ends_next_day' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    expect(fn () => DB::table('location_operating_hours')->insert([
        'location_id' => $locationId,
        'weekday' => 2,
        'sequence' => 1,
        'opens_at_time' => '09:00:00',
        'closes_at_time' => '08:00:00',
        'ends_next_day' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

test('location amenity and image attachments are unique per location and cascade with the location', function (): void {
    $vendor = Vendor::factory()->create();
    $uploader = User::factory()->create();

    $locationId = DB::table('locations')->insertGetId([
        'vendor_id' => $vendor->id,
        'name' => 'Whitefield Play Zone',
        'address_line_1' => '90 Main Road',
        'city' => 'Bengaluru',
        'state' => 'Karnataka',
        'postal_code' => '560066',
        'country_code' => 'IN',
        'timezone' => 'Asia/Kolkata',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $amenityId = DB::table('amenities')->insertGetId([
        'name' => 'Parking',
        'code' => 'parking',
        'description' => 'Open parking spaces',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $fileId = DB::table('files')->insertGetId([
        'purpose' => 'location_image',
        'status' => 'ready',
        'created_by_user_id' => $uploader->id,
        'vendor_id' => $vendor->id,
        'logical_disk' => 'public',
        'object_key' => 'location-images/whitefield-play-zone/main.jpg',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('location_amenities')->insert([
        'location_id' => $locationId,
        'amenity_id' => $amenityId,
    ]);

    DB::table('location_images')->insert([
        'location_id' => $locationId,
        'file_id' => $fileId,
        'sort_order' => 1,
        'caption' => 'Main entrance',
        'alt_text' => 'Outdoor view of the turf entrance',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => DB::table('location_amenities')->insert([
        'location_id' => $locationId,
        'amenity_id' => $amenityId,
    ]))->toThrow(QueryException::class);

    expect(fn () => DB::table('location_images')->insert([
        'location_id' => $locationId,
        'file_id' => $fileId,
        'sort_order' => 2,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    DB::table('locations')->where('id', $locationId)->delete();

    expect(DB::table('location_operating_hours')->where('location_id', $locationId)->exists())->toBeFalse()
        ->and(DB::table('location_amenities')->where('location_id', $locationId)->exists())->toBeFalse()
        ->and(DB::table('location_images')->where('location_id', $locationId)->exists())->toBeFalse();
});

test('location schema provides the documented ownership and geo indexes', function (): void {
    $indexesFor = static fn (string $table): array => collect(DB::select("PRAGMA index_list('{$table}')"))
        ->pluck('name')
        ->all();

    expect($indexesFor('locations'))
        ->toContain('locations_vendor_id_index')
        ->toContain('locations_status_index')
        ->toContain('locations_latitude_longitude_idx')
        ->toContain('locations_status_city_locality_index');
    expect($indexesFor('location_operating_hours'))
        ->toContain('location_operating_hours_location_id_weekday_index')
        ->toContain('location_operating_hours_location_id_weekday_sequence_unique');
    expect($indexesFor('location_amenities'))
        ->toContain('location_amenities_amenity_id_index')
        ->toContain('location_amenities_location_id_amenity_id_unique');
    expect($indexesFor('location_images'))
        ->toContain('location_images_location_id_sort_order_index')
        ->toContain('location_images_location_id_file_id_unique');
});
