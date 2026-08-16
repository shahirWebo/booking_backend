<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

beforeEach(function () {
    app(DatabaseSeeder::class)->run();

    Route::middleware('auth:sanctum')->prefix('api/v1/testing/rbac')->group(function (): void {
        Route::get('admin-support', fn () => response()->json(['authorized' => true]))
            ->middleware('role:admin_support');
        Route::get('customers', fn () => response()->json(['authorized' => true]))
            ->middleware('permission:view_customers');
    });
});

test('role middleware requires authentication and the exact configured role', function () {
    $customer = User::factory()->create();
    $customer->roles()->attach(Role::query()->where('code', 'customer')->firstOrFail());

    getJson('/api/v1/testing/rbac/admin-support')
        ->assertUnauthorized()
        ->assertJsonPath('code', 'UNAUTHENTICATED');

    actingAs($customer);

    getJson('/api/v1/testing/rbac/admin-support')
        ->assertForbidden()
        ->assertJsonPath('code', 'FORBIDDEN')
        ->assertJsonMissingPath('permission');

    $customer->roles()->sync([
        Role::query()->where('code', 'admin_support')->firstOrFail()->id,
    ]);

    getJson('/api/v1/testing/rbac/admin-support')
        ->assertOk()
        ->assertJson(['authorized' => true]);
});

test('permission middleware uses current role grants and denies a role without the capability', function () {
    $user = User::factory()->create();
    $supportRole = Role::query()->where('code', 'admin_support')->firstOrFail();
    $customerRole = Role::query()->where('code', 'customer')->firstOrFail();
    $user->roles()->attach($customerRole);

    actingAs($user);

    getJson('/api/v1/testing/rbac/customers')
        ->assertForbidden()
        ->assertJsonPath('code', 'FORBIDDEN')
        ->assertJsonMissingPath('permission');

    $user->roles()->sync([$supportRole->id]);

    getJson('/api/v1/testing/rbac/customers')
        ->assertOk()
        ->assertJson(['authorized' => true]);

    $supportRole->permissions()->detach();

    getJson('/api/v1/testing/rbac/customers')
        ->assertForbidden()
        ->assertJsonPath('code', 'FORBIDDEN');
});
