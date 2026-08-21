<?php

use App\Models\Amenity;
use App\Models\Role;
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

test('admin amenities routes require authentication', function () {
    getJson(route('api.v1.admin.amenities.index'))
        ->assertUnauthorized()
        ->assertJsonPath('code', 'UNAUTHENTICATED');
});

test('admin amenity crud requires the manage amenities permission', function () {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('code', 'admin_support')->firstOrFail());
    $token = $user->createToken('admin-amenities-support');

    withToken($token->plainTextToken);

    getJson(route('api.v1.admin.amenities.index'))
        ->assertForbidden()
        ->assertJsonPath('code', 'FORBIDDEN');
});

test('authorized admins can list, create, show, update, and delete amenities', function () {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('code', 'admin_operations')->firstOrFail());
    $token = $user->createToken('admin-amenities-operations');

    withToken($token->plainTextToken);

    getJson(route('api.v1.admin.amenities.index'))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(6, 'data');

    $createResponse = postJson(route('api.v1.admin.amenities.store'), [
        'name' => 'Drinking Water',
        'code' => 'drinking_water',
        'description' => 'Filtered drinking water is available on-site.',
        'is_active' => false,
    ])
        ->assertCreated()
        ->assertHeader('Location')
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Amenity created.')
        ->assertJsonPath('data.name', 'Drinking Water')
        ->assertJsonPath('data.code', 'drinking_water')
        ->assertJsonPath('data.is_active', false);

    $amenityId = $createResponse->json('data.id');

    getJson(route('api.v1.admin.amenities.show', $amenityId))
        ->assertOk()
        ->assertJsonPath('data.description', 'Filtered drinking water is available on-site.')
        ->assertJsonPath('data.is_active', false);

    putJson(route('api.v1.admin.amenities.update', $amenityId), [
        'name' => 'Locker Room',
        'code' => 'locker_room',
        'description' => 'Secure locker-room storage for players.',
        'is_active' => true,
    ])
        ->assertOk()
        ->assertJsonPath('message', 'Amenity updated.')
        ->assertJsonPath('data.name', 'Locker Room')
        ->assertJsonPath('data.code', 'locker_room')
        ->assertJsonPath('data.is_active', true);

    getJson(route('api.v1.admin.amenities.index'))
        ->assertOk()
        ->assertJsonCount(7, 'data');

    getJson(route('api.v1.admin.amenities.show', $amenityId))
        ->assertOk()
        ->assertJsonPath('data.name', 'Locker Room')
        ->assertJsonPath('data.description', 'Secure locker-room storage for players.')
        ->assertJsonPath('data.is_active', true);

    deleteJson(route('api.v1.admin.amenities.destroy', $amenityId))
        ->assertNoContent();

    expect(Amenity::query()->whereKey($amenityId)->exists())->toBeFalse();
});

test('admin amenity create and update validate unique fields and supported code format', function () {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('code', 'super_admin')->firstOrFail());
    $token = $user->createToken('admin-amenities-super-admin');

    $amenity = Amenity::query()->where('code', 'parking')->sole();

    withToken($token->plainTextToken);

    postJson(route('api.v1.admin.amenities.store'), [
        'name' => 'Parking',
        'code' => 'Parking',
        'description' => 123,
        'is_active' => 'sometimes',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'VALIDATION_ERROR')
        ->assertJsonStructure(['errors' => ['name', 'code', 'description', 'is_active']]);

    $otherAmenity = Amenity::query()->where('code', 'washroom')->sole();

    putJson(route('api.v1.admin.amenities.update', $otherAmenity), [
        'name' => 'Parking',
        'code' => 'parking',
        'description' => 'Updated description',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'VALIDATION_ERROR')
        ->assertJsonStructure(['errors' => ['name', 'code']]);

    expect($amenity->fresh()?->name)->toBe('Parking');
});
