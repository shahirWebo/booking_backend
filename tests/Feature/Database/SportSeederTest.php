<?php

use App\Models\Sport;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SportSeeder;

test('database seeding creates the canonical sports catalog', function () {
    app(DatabaseSeeder::class)->run();

    expect(Sport::query()->orderBy('code')->get(['name', 'code', 'is_active', 'icon_asset_key', 'image_asset_key'])->map(
        static fn (Sport $sport): array => [
            'name' => $sport->name,
            'code' => $sport->code,
            'is_active' => $sport->is_active,
            'icon_asset_key' => $sport->icon_asset_key,
            'image_asset_key' => $sport->image_asset_key,
        ],
    )->all())->toBe([
        ['name' => 'Badminton', 'code' => 'badminton', 'is_active' => true, 'icon_asset_key' => 'sports/icons/badminton.png', 'image_asset_key' => 'sports/images/badminton.png'],
        ['name' => 'Box Cricket', 'code' => 'box_cricket', 'is_active' => true, 'icon_asset_key' => 'sports/icons/box_cricket.png', 'image_asset_key' => 'sports/images/box_cricket.png'],
        ['name' => 'Cricket', 'code' => 'cricket', 'is_active' => true, 'icon_asset_key' => 'sports/icons/cricket.png', 'image_asset_key' => 'sports/images/cricket.png'],
        ['name' => 'Football', 'code' => 'football', 'is_active' => true, 'icon_asset_key' => 'sports/icons/football.png', 'image_asset_key' => 'sports/images/football.png'],
        ['name' => 'Tennis', 'code' => 'tennis', 'is_active' => true, 'icon_asset_key' => 'sports/icons/tennis.png', 'image_asset_key' => 'sports/images/tennis.png'],
    ]);
});

test('sport seeding is repeatable and restores canonical values without removing other sports', function () {
    app(SportSeeder::class)->run();

    $football = Sport::query()->where('code', 'football')->sole();
    $football->update([
        'name' => 'Changed Football',
        'description' => null,
        'is_active' => false,
        'icon_asset_key' => null,
        'icon_alt_text' => null,
        'image_asset_key' => null,
        'image_alt_text' => null,
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
        ->and($reloadedFootball->icon_asset_key)->toBe('sports/icons/football.png')
        ->and($reloadedFootball->icon_alt_text)->toBe('Football sport icon')
        ->and($reloadedFootball->image_asset_key)->toBe('sports/images/football.png')
        ->and($reloadedFootball->image_alt_text)->toBe('Football sport image')
        ->and(Sport::query()->where('code', 'pickleball')->exists())->toBeTrue();
});
