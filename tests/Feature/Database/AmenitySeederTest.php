<?php

use App\Models\Amenity;
use Database\Seeders\AmenitySeeder;
use Database\Seeders\DatabaseSeeder;

test('database seeding creates the canonical amenity catalog', function () {
    app(DatabaseSeeder::class)->run();

    expect(Amenity::query()->orderBy('code')->get(['name', 'code', 'description', 'is_active'])->map(
        static fn (Amenity $amenity): array => [
            'name' => $amenity->name,
            'code' => $amenity->code,
            'description' => $amenity->description,
            'is_active' => $amenity->is_active,
        ],
    )->all())->toBe([
        [
            'name' => 'Changing Room',
            'code' => 'changing_room',
            'description' => 'Dedicated changing space is available before and after play.',
            'is_active' => true,
        ],
        [
            'name' => 'Floodlights',
            'code' => 'floodlights',
            'description' => 'Floodlighting supports evening and low-light play.',
            'is_active' => true,
        ],
        [
            'name' => 'Locker',
            'code' => 'locker',
            'description' => 'Secure lockers are available for personal belongings.',
            'is_active' => true,
        ],
        [
            'name' => 'Parking',
            'code' => 'parking',
            'description' => 'Vehicle parking is available for players and visitors.',
            'is_active' => true,
        ],
        [
            'name' => 'Shower',
            'code' => 'shower',
            'description' => 'Shower facilities are available on-site.',
            'is_active' => true,
        ],
        [
            'name' => 'Washroom',
            'code' => 'washroom',
            'description' => 'On-site restroom facilities are available.',
            'is_active' => true,
        ],
    ]);
});

test('amenity seeding is repeatable and restores canonical values without removing other amenities', function () {
    app(AmenitySeeder::class)->run();

    $parking = Amenity::query()->where('code', 'parking')->sole();
    $parking->update([
        'name' => 'Changed Parking',
        'description' => null,
        'is_active' => false,
    ]);

    Amenity::query()->create([
        'name' => 'Cafe',
        'code' => 'cafe',
        'description' => 'A custom amenity managed outside the canonical seed catalog.',
        'is_active' => false,
    ]);

    app(AmenitySeeder::class)->run();

    $reloadedParking = Amenity::query()->findOrFail($parking->id);

    expect(Amenity::query()->count())->toBe(7)
        ->and($reloadedParking->id)->toBe($parking->id)
        ->and($reloadedParking->name)->toBe('Parking')
        ->and($reloadedParking->description)->toBe('Vehicle parking is available for players and visitors.')
        ->and($reloadedParking->is_active)->toBeTrue()
        ->and(Amenity::query()->where('code', 'cafe')->exists())->toBeTrue();
});
