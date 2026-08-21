<?php

use App\Models\Sport;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

test('sports table stores the display name and stable unique code', function () {
    expect(Schema::hasColumns('sports', [
        'id',
        'name',
        'code',
        'description',
        'is_active',
        'icon_asset_key',
        'icon_alt_text',
        'image_asset_key',
        'image_alt_text',
        'created_at',
        'updated_at',
    ]))->toBeTrue();

    $sport = Sport::query()->create([
        'name' => 'Football',
        'code' => 'football',
        'description' => 'Association football supported for turf discovery and booking.',
        'is_active' => false,
        'icon_asset_key' => 'sports/icons/football.png',
        'icon_alt_text' => 'Football sport icon',
        'image_asset_key' => 'sports/images/football.png',
        'image_alt_text' => 'Football sport image',
    ]);

    expect($sport)->toBeInstanceOf(Sport::class);
    expect($sport->id)->toBeInt();
    expect($sport->name)->toBe('Football');
    expect($sport->code)->toBe('football');
    expect($sport->description)->toBe(
        'Association football supported for turf discovery and booking.',
    );
    expect($sport->is_active)->toBeFalse();
    expect($sport->icon_asset_key)->toBe('sports/icons/football.png');
    expect($sport->icon_alt_text)->toBe('Football sport icon');
    expect($sport->image_asset_key)->toBe('sports/images/football.png');
    expect($sport->image_alt_text)->toBe('Football sport image');
});

test('sports default to active status when a status is not provided', function () {
    $sport = Sport::query()->create([
        'name' => 'Cricket',
        'code' => 'cricket',
    ]);

    expect($sport->refresh()->is_active)->toBeTrue();
});

test('sports require unique display names and stable codes', function () {
    Sport::query()->create([
        'name' => 'Football',
        'code' => 'football',
    ]);

    expect(fn () => Sport::query()->create([
        'name' => 'Football',
        'code' => 'association_football',
    ]))->toThrow(QueryException::class);

    expect(fn () => Sport::query()->create([
        'name' => 'Box Cricket',
        'code' => 'football',
    ]))->toThrow(QueryException::class);
});
