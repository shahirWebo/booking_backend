<?php

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('turf tables expose location ownership, classification, media, and rule columns', function (): void {
    expect(Schema::hasColumns('turfs', [
        'location_id',
        'name',
        'description',
        'status',
        'surface_type',
        'is_indoor',
        'capacity_count',
        'length_meters',
        'width_meters',
    ]))->toBeTrue();

    expect(Schema::hasColumns('turf_sports', [
        'turf_id',
        'sport_id',
    ]))->toBeTrue();

    expect(Schema::hasColumns('turf_images', [
        'turf_id',
        'file_id',
        'sort_order',
        'caption',
        'alt_text',
    ]))->toBeTrue();

    expect(Schema::hasColumns('turf_amenities', [
        'turf_id',
        'amenity_id',
    ]))->toBeTrue();

    expect(Schema::hasColumns('turf_rules', [
        'turf_id',
        'title',
        'description',
        'sort_order',
        'is_active',
    ]))->toBeTrue();
});

test('turfs enforce location ownership, valid status, and positive capacity and dimensions', function (): void {
    $vendor = Vendor::factory()->create();
    $locationId = DB::table('locations')->insertGetId([
        'vendor_id' => $vendor->id,
        'name' => 'HSR Sports Complex',
        'address_line_1' => '15 Club Road',
        'city' => 'Bengaluru',
        'state' => 'Karnataka',
        'postal_code' => '560102',
        'country_code' => 'IN',
        'timezone' => 'Asia/Kolkata',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $turfId = DB::table('turfs')->insertGetId([
        'location_id' => $locationId,
        'name' => 'Five-a-side Arena',
        'description' => 'Primary football turf.',
        'status' => 'active',
        'surface_type' => 'artificial_grass',
        'is_indoor' => false,
        'capacity_count' => 10,
        'length_meters' => '40.00',
        'width_meters' => '20.00',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(DB::table('turfs')->where('id', $turfId)->exists())->toBeTrue();

    expect(fn () => DB::table('turfs')->insert([
        'location_id' => $locationId,
        'name' => 'Broken Status Turf',
        'status' => 'draft',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    expect(fn () => DB::table('turfs')->insert([
        'location_id' => $locationId,
        'name' => 'Negative Capacity Turf',
        'status' => 'inactive',
        'capacity_count' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    expect(fn () => DB::table('turfs')->insert([
        'location_id' => $locationId,
        'name' => 'Half Dimension Turf',
        'status' => 'inactive',
        'length_meters' => '36.00',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

test('turf mappings, images, and rules are unique per turf and cascade with the turf', function (): void {
    $vendor = Vendor::factory()->create();
    $uploader = User::factory()->create();

    $locationId = DB::table('locations')->insertGetId([
        'vendor_id' => $vendor->id,
        'name' => 'JP Nagar Arena',
        'address_line_1' => '87 Sports Lane',
        'city' => 'Bengaluru',
        'state' => 'Karnataka',
        'postal_code' => '560078',
        'country_code' => 'IN',
        'timezone' => 'Asia/Kolkata',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $turfId = DB::table('turfs')->insertGetId([
        'location_id' => $locationId,
        'name' => 'Cricket Box',
        'status' => 'inactive',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $sportId = DB::table('sports')->insertGetId([
        'name' => 'Box Cricket',
        'code' => 'box_cricket_schema',
        'description' => 'Compact-format cricket for enclosed turf play.',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $amenityId = DB::table('amenities')->insertGetId([
        'name' => 'Scoreboard',
        'code' => 'scoreboard_schema',
        'description' => 'Digital scoreboard for the turf.',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $fileId = DB::table('files')->insertGetId([
        'purpose' => 'turf_image',
        'status' => 'ready',
        'created_by_user_id' => $uploader->id,
        'vendor_id' => $vendor->id,
        'logical_disk' => 'public',
        'object_key' => 'turf-images/jp-nagar-arena/cricket-box.jpg',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('turf_sports')->insert([
        'turf_id' => $turfId,
        'sport_id' => $sportId,
    ]);

    DB::table('turf_amenities')->insert([
        'turf_id' => $turfId,
        'amenity_id' => $amenityId,
    ]);

    DB::table('turf_images')->insert([
        'turf_id' => $turfId,
        'file_id' => $fileId,
        'sort_order' => 1,
        'caption' => 'Night match setup',
        'alt_text' => 'Enclosed cricket box turf under floodlights',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('turf_rules')->insert([
        'turf_id' => $turfId,
        'title' => 'Non-marking shoes only',
        'description' => 'Players must wear non-marking shoes on the turf surface.',
        'sort_order' => 1,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => DB::table('turf_sports')->insert([
        'turf_id' => $turfId,
        'sport_id' => $sportId,
    ]))->toThrow(QueryException::class);

    expect(fn () => DB::table('turf_amenities')->insert([
        'turf_id' => $turfId,
        'amenity_id' => $amenityId,
    ]))->toThrow(QueryException::class);

    expect(fn () => DB::table('turf_images')->insert([
        'turf_id' => $turfId,
        'file_id' => $fileId,
        'sort_order' => 2,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    expect(fn () => DB::table('turf_rules')->insert([
        'turf_id' => $turfId,
        'title' => 'Duplicate order',
        'description' => 'This should fail.',
        'sort_order' => 1,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    DB::table('turfs')->where('id', $turfId)->delete();

    expect(DB::table('turf_sports')->where('turf_id', $turfId)->exists())->toBeFalse()
        ->and(DB::table('turf_amenities')->where('turf_id', $turfId)->exists())->toBeFalse()
        ->and(DB::table('turf_images')->where('turf_id', $turfId)->exists())->toBeFalse()
        ->and(DB::table('turf_rules')->where('turf_id', $turfId)->exists())->toBeFalse();
});

test('turf schema provides the documented ownership, filter, and attachment indexes', function (): void {
    $indexesFor = static fn (string $table): array => collect(DB::select("PRAGMA index_list('{$table}')"))
        ->pluck('name')
        ->all();

    expect($indexesFor('turfs'))
        ->toContain('turfs_location_id_index')
        ->toContain('turfs_status_index')
        ->toContain('turfs_location_id_status_index')
        ->toContain('turfs_surface_type_index')
        ->toContain('turfs_is_indoor_index')
        ->toContain('turfs_name_index');
    expect($indexesFor('turf_sports'))
        ->toContain('turf_sports_sport_id_index')
        ->toContain('turf_sports_turf_id_sport_id_unique');
    expect($indexesFor('turf_images'))
        ->toContain('turf_images_turf_id_sort_order_index')
        ->toContain('turf_images_turf_id_file_id_unique');
    expect($indexesFor('turf_amenities'))
        ->toContain('turf_amenities_amenity_id_index')
        ->toContain('turf_amenities_turf_id_amenity_id_unique');
    expect($indexesFor('turf_rules'))
        ->toContain('turf_rules_turf_id_sort_order_unique')
        ->toContain('turf_rules_turf_id_is_active_index');
});
