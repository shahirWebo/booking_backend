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
        ->assertJsonCount(0, 'data');

    $createResponse = postJson(route('api.v1.admin.amenities.store'), [
        'name' => 'Parking',
        'code' => 'parking',
        'description' => 'Vehicle parking available at the venue.',
    ])
        ->assertCreated()
        ->assertHeader('Location')
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Amenity created.')
        ->assertJsonPath('data.name', 'Parking')
        ->assertJsonPath('data.code', 'parking');

    $amenityId = $createResponse->json('data.id');

    getJson(route('api.v1.admin.amenities.show', $amenityId))
        ->assertOk()
        ->assertJsonPath('data.description', 'Vehicle parking available at the venue.');

    putJson(route('api.v1.admin.amenities.update', $amenityId), [
        'name' => 'Locker Room',
        'code' => 'locker_room',
        'description' => 'Secure locker-room storage for players.',
    ])
        ->assertOk()
        ->assertJsonPath('message', 'Amenity updated.')
        ->assertJsonPath('data.name', 'Locker Room')
        ->assertJsonPath('data.code', 'locker_room');

    getJson(route('api.v1.admin.amenities.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data');

    getJson(route('api.v1.admin.amenities.show', $amenityId))
        ->assertOk()
        ->assertJsonPath('data.name', 'Locker Room')
        ->assertJsonPath('data.description', 'Secure locker-room storage for players.');

    deleteJson(route('api.v1.admin.amenities.destroy', $amenityId))
        ->assertNoContent();

    expect(Amenity::query()->whereKey($amenityId)->exists())->toBeFalse();
});

test('admin amenity create and update validate unique fields and supported code format', function () {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('code', 'super_admin')->firstOrFail());
    $token = $user->createToken('admin-amenities-super-admin');

    $amenity = Amenity::query()->create([
        'name' => 'Parking',
        'code' => 'parking',
        'description' => 'Vehicle parking available at the venue.',
    ]);

    withToken($token->plainTextToken);

    postJson(route('api.v1.admin.amenities.store'), [
        'name' => 'Parking',
        'code' => 'Parking',
        'description' => 123,
    ])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'VALIDATION_ERROR')
        ->assertJsonStructure(['errors' => ['name', 'code', 'description']]);

    $otherAmenity = Amenity::query()->create([
        'name' => 'Washroom',
        'code' => 'washroom',
        'description' => 'Restroom facilities.',
    ]);

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
