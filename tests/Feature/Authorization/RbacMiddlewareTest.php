<?php

use App\Domain\Users\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\withToken;

beforeEach(function () {
    app(DatabaseSeeder::class)->run();

    Route::middleware(['auth:sanctum', 'active-user'])->prefix('api/v1/testing/rbac')->group(function (): void {
        Route::get('admin-support', fn () => response()->json(['authorized' => true]))
            ->middleware('role:admin_support');
        Route::get('customers', fn () => response()->json(['authorized' => true]))
            ->middleware('permission:view_customers');
        Route::get('unknown-role', fn () => response()->json(['authorized' => true]))
            ->middleware('role:unknown_role_code');
        Route::get('unknown-permission', fn () => response()->json(['authorized' => true]))
            ->middleware('permission:unknown_permission_code');

        foreach ([
            'access_admin',
            'check_in_bookings',
            'manage_customer_status',
            'manage_pricing',
            'manage_refunds',
            'manage_roles_and_permissions',
            'manage_support_tickets',
            'manage_vendor_staff',
            'suspend_vendors',
            'view_vendor_finance',
        ] as $permissionCode) {
            Route::get(
                "permissions/{$permissionCode}",
                fn () => response()->json(['authorized' => true]),
            )->middleware("permission:{$permissionCode}");
        }
    });
});

test('role middleware requires authentication and the exact configured role', function () {
    $customer = User::factory()->create();
    $customer->roles()->attach(Role::query()->where('code', 'customer')->firstOrFail());

    getJson('/api/v1/testing/rbac/admin-support')
        ->assertUnauthorized()
        ->assertJsonPath('code', 'UNAUTHENTICATED');

    getJson('/api/v1/testing/rbac/customers')
        ->assertUnauthorized()
        ->assertJsonPath('code', 'UNAUTHENTICATED');

    actingAs($customer);

    getJson('/api/v1/testing/rbac/admin-support')
        ->assertForbidden()
        ->assertJsonPath('code', 'FORBIDDEN')
        ->assertJsonMissingPath('permission')
        ->assertDontSee('admin_support');

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
        ->assertJsonMissingPath('permission')
        ->assertDontSee('view_customers');

    $user->roles()->sync([$supportRole->id]);

    getJson('/api/v1/testing/rbac/customers')
        ->assertOk()
        ->assertJson(['authorized' => true]);

    $supportRole->permissions()->detach();

    getJson('/api/v1/testing/rbac/customers')
        ->assertForbidden()
        ->assertJsonPath('code', 'FORBIDDEN');
});

test('unknown role and permission codes fail closed without disclosing configuration', function () {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('code', 'super_admin')->firstOrFail());
    actingAs($user);

    getJson('/api/v1/testing/rbac/unknown-role')
        ->assertForbidden()
        ->assertJsonPath('code', 'FORBIDDEN')
        ->assertDontSee('unknown_role_code');

    getJson('/api/v1/testing/rbac/unknown-permission')
        ->assertForbidden()
        ->assertJsonPath('code', 'FORBIDDEN')
        ->assertDontSee('unknown_permission_code');
});

test('permissions are combined across roles and membership revocation takes effect immediately', function () {
    $user = User::factory()->create();
    $accountantRole = Role::query()->where('code', 'vendor_accountant')->firstOrFail();
    $managerRole = Role::query()->where('code', 'vendor_manager')->firstOrFail();
    $user->roles()->attach($accountantRole);
    actingAs($user);

    getJson('/api/v1/testing/rbac/permissions/view_vendor_finance')->assertOk();
    getJson('/api/v1/testing/rbac/permissions/manage_pricing')->assertForbidden();

    $user->roles()->attach($managerRole);

    getJson('/api/v1/testing/rbac/permissions/view_vendor_finance')->assertOk();
    getJson('/api/v1/testing/rbac/permissions/manage_pricing')->assertOk();

    $user->roles()->detach($managerRole);

    getJson('/api/v1/testing/rbac/permissions/view_vendor_finance')->assertOk();
    getJson('/api/v1/testing/rbac/permissions/manage_pricing')->assertForbidden();
});

test('canonical roles enforce representative least-privilege boundaries', function (
    string $roleCode,
    string $allowedPermission,
    string $deniedPermission,
) {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('code', $roleCode)->firstOrFail());
    actingAs($user);

    getJson("/api/v1/testing/rbac/permissions/{$allowedPermission}")->assertOk();
    getJson("/api/v1/testing/rbac/permissions/{$deniedPermission}")
        ->assertForbidden()
        ->assertJsonPath('code', 'FORBIDDEN')
        ->assertDontSee($deniedPermission);
})->with([
    'admin operations cannot issue refunds' => ['admin_operations', 'suspend_vendors', 'manage_refunds'],
    'admin support cannot change customer status' => ['admin_support', 'manage_support_tickets', 'manage_customer_status'],
    'admin finance cannot suspend vendors' => ['admin_finance', 'manage_refunds', 'suspend_vendors'],
    'vendor manager cannot view vendor finance' => ['vendor_manager', 'manage_pricing', 'view_vendor_finance'],
    'vendor staff cannot manage staff' => ['vendor_staff', 'check_in_bookings', 'manage_vendor_staff'],
    'vendor accountant cannot manage pricing' => ['vendor_accountant', 'view_vendor_finance', 'manage_pricing'],
    'vendor owner has no platform admin access' => ['vendor_owner', 'view_vendor_finance', 'access_admin'],
]);

test('blocked and suspended accounts are rejected before permission evaluation', function (
    UserStatus $status,
    string $errorCode,
) {
    $user = User::factory()->create(['status' => UserStatus::Active]);
    $user->roles()->attach(Role::query()->where('code', 'super_admin')->firstOrFail());
    $token = $user->createToken('restricted-rbac-test');
    $user->update(['status' => $status]);

    withToken($token->plainTextToken);

    getJson('/api/v1/testing/rbac/permissions/manage_roles_and_permissions')
        ->assertForbidden()
        ->assertJsonPath('code', $errorCode)
        ->assertDontSee('manage_roles_and_permissions');

    expect($token->accessToken->fresh())->toBeNull();
})->with([
    'blocked account' => [UserStatus::Blocked, 'USER_BLOCKED'],
    'suspended account' => [UserStatus::Suspended, 'USER_SUSPENDED'],
]);

test('empty role and permission codes never authorize', function () {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('code', 'super_admin')->firstOrFail());

    expect($user->hasRole(''))->toBeFalse()
        ->and($user->hasPermission(''))->toBeFalse();
});
