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
        ->assertJsonPath('data', []);

    $createResponse = postJson(route('api.v1.admin.sports.store'), [
        'name' => 'Football',
        'code' => 'football',
        'description' => 'Association football supported for turf discovery and booking.',
        'is_active' => false,
    ])
        ->assertCreated()
        ->assertHeader('Location')
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Sport created.')
        ->assertJsonPath('data.name', 'Football')
        ->assertJsonPath('data.code', 'football')
        ->assertJsonPath('data.is_active', false);

    $sportId = $createResponse->json('data.id');

    getJson(route('api.v1.admin.sports.show', $sportId))
        ->assertOk()
        ->assertJsonPath('data.description', 'Association football supported for turf discovery and booking.')
        ->assertJsonPath('data.is_active', false);

    putJson(route('api.v1.admin.sports.update', $sportId), [
        'name' => 'Box Cricket',
        'code' => 'box_cricket',
        'description' => 'Compact-format cricket commonly played on turfs.',
        'is_active' => true,
    ])
        ->assertOk()
        ->assertJsonPath('message', 'Sport updated.')
        ->assertJsonPath('data.name', 'Box Cricket')
        ->assertJsonPath('data.code', 'box_cricket')
        ->assertJsonPath('data.is_active', true);

    getJson(route('api.v1.admin.sports.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Box Cricket')
        ->assertJsonPath('data.0.is_active', true);

    deleteJson(route('api.v1.admin.sports.destroy', $sportId))
        ->assertNoContent();

    expect(Sport::query()->whereKey($sportId)->exists())->toBeFalse();
});

test('admin sport create and update validate unique fields and supported code format', function () {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('code', 'super_admin')->firstOrFail());
    $token = $user->createToken('admin-sports-super-admin');

    $sport = Sport::query()->create([
        'name' => 'Football',
        'code' => 'football',
        'description' => 'Association football.',
    ]);

    withToken($token->plainTextToken);

    postJson(route('api.v1.admin.sports.store'), [
        'name' => 'Football',
        'code' => 'Football',
        'description' => 123,
        'is_active' => 'sometimes',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'VALIDATION_ERROR')
        ->assertJsonStructure(['errors' => ['name', 'code', 'description', 'is_active']]);

    $otherSport = Sport::query()->create([
        'name' => 'Cricket',
        'code' => 'cricket',
    ]);

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
