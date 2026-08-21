<?php

use App\Models\Amenity;
use Database\Seeders\AmenitySeeder;
use Database\Seeders\DatabaseSeeder;

test('database seeding creates the canonical amenity catalog', function () {
    app(DatabaseSeeder::class)->run();

    expect(Amenity::query()->orderBy('code')->get(['name', 'code', 'description'])->map(
        static fn (Amenity $amenity): array => [
            'name' => $amenity->name,
            'code' => $amenity->code,
            'description' => $amenity->description,
        ],
    )->all())->toBe([
        [
            'name' => 'Changing Room',
            'code' => 'changing_room',
            'description' => 'Dedicated changing space is available before and after play.',
        ],
        [
            'name' => 'Floodlights',
            'code' => 'floodlights',
            'description' => 'Floodlighting supports evening and low-light play.',
        ],
        [
            'name' => 'Locker',
            'code' => 'locker',
            'description' => 'Secure lockers are available for personal belongings.',
        ],
        [
            'name' => 'Parking',
            'code' => 'parking',
            'description' => 'Vehicle parking is available for players and visitors.',
        ],
        [
            'name' => 'Shower',
            'code' => 'shower',
            'description' => 'Shower facilities are available on-site.',
        ],
        [
            'name' => 'Washroom',
            'code' => 'washroom',
            'description' => 'On-site restroom facilities are available.',
        ],
    ]);
});

test('amenity seeding is repeatable and restores canonical values without removing other amenities', function () {
    app(AmenitySeeder::class)->run();

    $parking = Amenity::query()->where('code', 'parking')->sole();
    $parking->update([
        'name' => 'Changed Parking',
        'description' => null,
    ]);

    Amenity::query()->create([
        'name' => 'Cafe',
        'code' => 'cafe',
        'description' => 'A custom amenity managed outside the canonical seed catalog.',
    ]);

    app(AmenitySeeder::class)->run();

    $reloadedParking = Amenity::query()->findOrFail($parking->id);

    expect(Amenity::query()->count())->toBe(7)
        ->and($reloadedParking->id)->toBe($parking->id)
        ->and($reloadedParking->name)->toBe('Parking')
        ->and($reloadedParking->description)->toBe('Vehicle parking is available for players and visitors.')
        ->and(Amenity::query()->where('code', 'cafe')->exists())->toBeTrue();
});
