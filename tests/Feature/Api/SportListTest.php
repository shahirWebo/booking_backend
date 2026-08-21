<?php

use App\Models\Sport;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(DatabaseSeeder::class)->run();
});

test('public sports index returns active sports ordered by name', function () {
    Sport::query()->where('code', 'cricket')->sole()->update([
        'is_active' => false,
    ]);

    getJson(route('api.v1.sports.index'))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('meta.request_id', fn (mixed $value): bool => is_string($value) && $value !== '')
        ->assertJsonCount(4, 'data')
        ->assertJsonPath('data.0.name', 'Badminton')
        ->assertJsonPath('data.0.code', 'badminton')
        ->assertJsonPath('data.0.icon_asset_key', 'sports/icons/badminton.png')
        ->assertJsonPath('data.0.image_asset_key', 'sports/images/badminton.png')
        ->assertJsonMissingPath('data.0.is_active')
        ->assertJsonMissingPath('data.0.created_at')
        ->assertJsonMissingPath('data.0.updated_at');
});

test('public sports index is available without authentication', function () {
    getJson(route('api.v1.sports.index'))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(5, 'data');
});
