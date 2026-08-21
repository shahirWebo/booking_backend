<?php

use App\Models\Sport;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SportSeeder;

test('database seeding creates the canonical sports catalog', function () {
    app(DatabaseSeeder::class)->run();

    expect(Sport::query()->orderBy('code')->get(['name', 'code', 'is_active'])->map(
        static fn (Sport $sport): array => [
            'name' => $sport->name,
            'code' => $sport->code,
            'is_active' => $sport->is_active,
        ],
    )->all())->toBe([
        ['name' => 'Badminton', 'code' => 'badminton', 'is_active' => true],
        ['name' => 'Box Cricket', 'code' => 'box_cricket', 'is_active' => true],
        ['name' => 'Cricket', 'code' => 'cricket', 'is_active' => true],
        ['name' => 'Football', 'code' => 'football', 'is_active' => true],
        ['name' => 'Tennis', 'code' => 'tennis', 'is_active' => true],
    ]);
});

test('sport seeding is repeatable and restores canonical values without removing other sports', function () {
    app(SportSeeder::class)->run();

    $football = Sport::query()->where('code', 'football')->sole();
    $football->update([
        'name' => 'Changed Football',
        'description' => null,
        'is_active' => false,
    ]);

    Sport::query()->create([
        'name' => 'Pickleball',
        'code' => 'pickleball',
        'description' => 'A custom sport managed outside the canonical seed catalog.',
        'is_active' => false,
    ]);

    app(SportSeeder::class)->run();

    $reloadedFootball = Sport::query()->findOrFail($football->id);

    expect(Sport::query()->count())->toBe(6)
        ->and($reloadedFootball->id)->toBe($football->id)
        ->and($reloadedFootball->name)->toBe('Football')
        ->and($reloadedFootball->description)->toBe('Association football for full-size or small-sided turf play.')
        ->and($reloadedFootball->is_active)->toBeTrue()
        ->and(Sport::query()->where('code', 'pickleball')->exists())->toBeTrue();
});
