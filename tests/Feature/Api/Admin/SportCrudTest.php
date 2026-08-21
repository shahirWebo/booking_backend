<?php

use App\Models\Role;
use App\Models\Sport;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;
use function Pest\Laravel\withToken;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(DatabaseSeeder::class)->run();
});

test('admin sports routes require authentication', function () {
    getJson(route('api.v1.admin.sports.index'))
        ->assertUnauthorized()
        ->assertJsonPath('code', 'UNAUTHENTICATED');
});

test('admin sport crud requires the manage sports permission', function () {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('code', 'admin_support')->firstOrFail());
    $token = $user->createToken('admin-sports-support');

    withToken($token->plainTextToken);

    getJson(route('api.v1.admin.sports.index'))
        ->assertForbidden()
        ->assertJsonPath('code', 'FORBIDDEN');
});

test('authorized admins can list, create, show, update, and delete sports', function () {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('code', 'admin_operations')->firstOrFail());
    $token = $user->createToken('admin-sports-operations');

    withToken($token->plainTextToken);

    getJson(route('api.v1.admin.sports.index'))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(5, 'data');

    $createResponse = postJson(route('api.v1.admin.sports.store'), [
        'name' => 'Volleyball',
        'code' => 'volleyball',
        'description' => 'Indoor and outdoor volleyball court bookings.',
        'is_active' => false,
        'icon_asset_key' => 'sports/icons/volleyball.png',
        'icon_alt_text' => 'Volleyball sport icon',
        'image_asset_key' => 'sports/images/volleyball.png',
        'image_alt_text' => 'Volleyball sport image',
    ])
        ->assertCreated()
        ->assertHeader('Location')
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Sport created.')
        ->assertJsonPath('data.name', 'Volleyball')
        ->assertJsonPath('data.code', 'volleyball')
        ->assertJsonPath('data.is_active', false)
        ->assertJsonPath('data.icon_asset_key', 'sports/icons/volleyball.png')
        ->assertJsonPath('data.image_asset_key', 'sports/images/volleyball.png');

    $sportId = $createResponse->json('data.id');

    getJson(route('api.v1.admin.sports.show', $sportId))
        ->assertOk()
        ->assertJsonPath('data.description', 'Indoor and outdoor volleyball court bookings.')
        ->assertJsonPath('data.is_active', false)
        ->assertJsonPath('data.icon_alt_text', 'Volleyball sport icon')
        ->assertJsonPath('data.image_alt_text', 'Volleyball sport image');

    putJson(route('api.v1.admin.sports.update', $sportId), [
        'name' => 'Pickleball',
        'code' => 'pickleball',
        'description' => 'Pickleball court bookings.',
        'is_active' => true,
        'icon_asset_key' => 'sports/icons/pickleball.png',
        'icon_alt_text' => 'Pickleball sport icon',
        'image_asset_key' => 'sports/images/pickleball.png',
        'image_alt_text' => 'Pickleball sport image',
    ])
        ->assertOk()
        ->assertJsonPath('message', 'Sport updated.')
        ->assertJsonPath('data.name', 'Pickleball')
        ->assertJsonPath('data.code', 'pickleball')
        ->assertJsonPath('data.is_active', true)
        ->assertJsonPath('data.icon_asset_key', 'sports/icons/pickleball.png')
        ->assertJsonPath('data.image_asset_key', 'sports/images/pickleball.png');

    getJson(route('api.v1.admin.sports.index'))
        ->assertOk()
        ->assertJsonCount(6, 'data');

    getJson(route('api.v1.admin.sports.show', $sportId))
        ->assertOk()
        ->assertJsonPath('data.name', 'Pickleball')
        ->assertJsonPath('data.is_active', true)
        ->assertJsonPath('data.icon_alt_text', 'Pickleball sport icon')
        ->assertJsonPath('data.image_alt_text', 'Pickleball sport image');

    deleteJson(route('api.v1.admin.sports.destroy', $sportId))
        ->assertNoContent();

    expect(Sport::query()->whereKey($sportId)->exists())->toBeFalse();
});

test('admin sport create and update validate unique fields and supported code format', function () {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('code', 'super_admin')->firstOrFail());
    $token = $user->createToken('admin-sports-super-admin');

    $sport = Sport::query()->where('code', 'football')->sole();

    withToken($token->plainTextToken);

    postJson(route('api.v1.admin.sports.store'), [
        'name' => 'Football',
        'code' => 'Football',
        'description' => 123,
        'is_active' => 'sometimes',
        'icon_asset_key' => 99,
        'icon_alt_text' => 99,
        'image_asset_key' => 99,
        'image_alt_text' => 99,
    ])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'VALIDATION_ERROR')
        ->assertJsonStructure(['errors' => ['name', 'code', 'description', 'is_active', 'icon_asset_key', 'icon_alt_text', 'image_asset_key', 'image_alt_text']]);

    $otherSport = Sport::query()->where('code', 'cricket')->sole();

    putJson(route('api.v1.admin.sports.update', $otherSport), [
        'name' => 'Football',
        'code' => 'football',
        'description' => 'Updated description',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'VALIDATION_ERROR')
        ->assertJsonStructure(['errors' => ['name', 'code']]);

    expect($sport->fresh()?->name)->toBe('Football');
});
