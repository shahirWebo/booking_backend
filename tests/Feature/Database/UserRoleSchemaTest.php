<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('user roles map users to roles through the explicit mapping table', function () {
    expect(Schema::hasColumns('user_roles', ['user_id', 'role_id']))->toBeTrue();

    $user = User::factory()->create();
    $role = Role::query()->create([
        'name' => 'Vendor Manager',
        'code' => 'vendor_manager',
    ]);

    $user->roles()->attach($role);

    expect($user->roles()->pluck('roles.id')->all())->toBe([$role->id])
        ->and($role->users()->pluck('users.id')->all())->toBe([$user->id]);
});

test('user role mappings are unique and removed with either related record', function () {
    $user = User::factory()->create();
    $role = Role::query()->create([
        'name' => 'Administrator',
        'code' => 'administrator',
    ]);

    DB::table('user_roles')->insert([
        'user_id' => $user->id,
        'role_id' => $role->id,
    ]);

    expect(fn () => DB::table('user_roles')->insert([
        'user_id' => $user->id,
        'role_id' => $role->id,
    ]))->toThrow(QueryException::class);

    $role->delete();

    expect(DB::table('user_roles')->where('user_id', $user->id)->exists())->toBeFalse();

    $replacementRole = Role::query()->create([
        'name' => 'Customer',
        'code' => 'customer',
    ]);

    $user->roles()->attach($replacementRole);
    $user->delete();

    expect(DB::table('user_roles')->where('role_id', $replacementRole->id)->exists())->toBeFalse();
});
