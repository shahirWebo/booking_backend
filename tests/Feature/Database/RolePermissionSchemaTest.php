<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('role permissions map roles to permissions through the explicit mapping table', function () {
    expect(Schema::hasColumns('role_permissions', ['role_id', 'permission_id']))->toBeTrue();

    $role = Role::query()->create([
        'name' => 'Vendor Manager',
        'code' => 'vendor_manager',
    ]);
    $permission = Permission::query()->create([
        'name' => 'Manage Vendor Bookings',
        'code' => 'vendor_bookings_manage',
    ]);

    $role->permissions()->attach($permission);

    expect($role->permissions()->pluck('permissions.id')->all())->toBe([$permission->id])
        ->and($permission->roles()->pluck('roles.id')->all())->toBe([$role->id]);
});

test('role permission mappings are unique and removed with either related record', function () {
    $role = Role::query()->create([
        'name' => 'Administrator',
        'code' => 'administrator',
    ]);
    $permission = Permission::query()->create([
        'name' => 'Manage Users',
        'code' => 'users_manage',
    ]);

    DB::table('role_permissions')->insert([
        'role_id' => $role->id,
        'permission_id' => $permission->id,
    ]);

    expect(fn () => DB::table('role_permissions')->insert([
        'role_id' => $role->id,
        'permission_id' => $permission->id,
    ]))->toThrow(QueryException::class);

    $permission->delete();

    expect(DB::table('role_permissions')->where('role_id', $role->id)->exists())->toBeFalse();

    $replacementPermission = Permission::query()->create([
        'name' => 'Manage Venues',
        'code' => 'venues_manage',
    ]);

    $role->permissions()->attach($replacementPermission);
    $role->delete();

    expect(DB::table('role_permissions')->where('permission_id', $replacementPermission->id)->exists())->toBeFalse();
});
