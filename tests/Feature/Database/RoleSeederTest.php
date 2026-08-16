<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RoleSeeder;

test('database seeding creates the canonical roles', function () {
    app(DatabaseSeeder::class)->run();

    expect(Role::query()->orderBy('code')->pluck('name', 'code')->all())->toBe([
        'admin_finance' => 'Admin Finance',
        'admin_operations' => 'Admin Operations',
        'admin_support' => 'Admin Support',
        'customer' => 'Customer',
        'super_admin' => 'Super Admin',
        'vendor_accountant' => 'Vendor Accountant',
        'vendor_manager' => 'Vendor Manager',
        'vendor_owner' => 'Vendor Owner',
        'vendor_staff' => 'Vendor Staff',
    ])->and(User::query()->exists())->toBeFalse();
});

test('role seeding is repeatable and restores canonical values without removing other roles', function () {
    app(RoleSeeder::class)->run();

    $vendorManager = Role::query()->where('code', 'vendor_manager')->sole();
    $vendorManager->update([
        'name' => 'Changed Manager',
        'description' => null,
    ]);

    Role::query()->create([
        'name' => 'Custom Integration Role',
        'code' => 'custom_integration',
        'description' => 'A role managed outside the canonical seed catalog.',
    ]);

    app(RoleSeeder::class)->run();

    $reloadedVendorManager = Role::query()->findOrFail($vendorManager->id);

    expect(Role::query()->count())->toBe(10)
        ->and($reloadedVendorManager->id)->toBe($vendorManager->id)
        ->and($reloadedVendorManager->name)->toBe('Vendor Manager')
        ->and($reloadedVendorManager->description)->toBe('Vendor role for managing authorized operations and staff scope.')
        ->and(Role::query()->where('code', 'custom_integration')->exists())->toBeTrue();
});
