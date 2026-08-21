<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guest surface pages expose empty auth shell metadata', function (): void {
    $this->get(route('customer.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/Home')
            ->where('auth.user', null)
            ->where('auth.roles', [])
            ->where('auth.permissions', [])
            ->where('auth.preferredSurface', null)
            ->where('auth.sessionMode', 'guest'),
        );
});

test('authenticated surface pages expose role-aware auth shell metadata', function (): void {
    $user = User::factory()->create();
    $permission = Permission::query()->create([
        'name' => 'View Vendor Finance',
        'code' => 'view_vendor_finance',
        'description' => 'Allows finance dashboards to be rendered.',
    ]);
    $role = Role::query()->create([
        'name' => 'Vendor Owner',
        'code' => 'vendor_owner',
        'description' => 'Primary vendor owner role used for web shell routing.',
    ]);
    $role->permissions()->attach($permission);
    $user->roles()->attach($role);

    $this->actingAs($user)
        ->get(route('vendor.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('vendor/Home')
            ->where('auth.user.id', $user->id)
            ->where('auth.roles', ['vendor_owner'])
            ->where('auth.permissions', ['view_vendor_finance'])
            ->where('auth.preferredSurface', 'vendor')
            ->where('auth.sessionMode', 'cookie'),
        );
});
