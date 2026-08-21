<?php

use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PermissionSeeder;

test('database seeding creates the canonical permission catalog and least-privilege role grants', function () {
    app(DatabaseSeeder::class)->run();

    $expectedCodes = [
        'access_admin',
        'access_vendor_portal',
        'add_booking_notes',
        'cancel_any_booking',
        'cancel_vendor_bookings',
        'check_in_bookings',
        'complete_bookings',
        'create_walk_in_bookings',
        'manage_availability',
        'manage_commissions',
        'manage_customer_status',
        'manage_locations',
        'manage_notifications',
        'manage_platform_coupons',
        'manage_pricing',
        'manage_refunds',
        'manage_roles_and_permissions',
        'manage_settlements',
        'manage_sports',
        'manage_support_tickets',
        'manage_system_settings',
        'manage_turf_status',
        'manage_turfs',
        'manage_vendor_coupons',
        'manage_vendor_profile',
        'manage_vendor_staff',
        'mark_no_show',
        'moderate_reviews',
        'reactivate_vendors',
        'reconcile_payments',
        'record_offline_payments',
        'reply_to_reviews',
        'reschedule_any_booking',
        'reschedule_vendor_bookings',
        'review_vendors',
        'suspend_vendors',
        'view_all_bookings',
        'view_all_payments',
        'view_all_turfs',
        'view_audit_logs',
        'view_customers',
        'view_platform_finance',
        'view_platform_reports',
        'view_vendor_bookings',
        'view_vendor_finance',
        'view_vendor_reports',
        'view_vendor_settlements',
        'view_vendors',
    ];

    $permissionCodesFor = static fn (string $roleCode): array => Role::query()
        ->where('code', $roleCode)
        ->firstOrFail()
        ->permissions()
        ->orderBy('code')
        ->pluck('code')
        ->all();

    expect(Permission::query()->orderBy('code')->pluck('code')->all())->toBe($expectedCodes)
        ->and($permissionCodesFor('super_admin'))->toBe($expectedCodes)
        ->and($permissionCodesFor('admin_support'))->toBe([
            'access_admin',
            'add_booking_notes',
            'manage_support_tickets',
            'view_all_bookings',
            'view_all_payments',
            'view_all_turfs',
            'view_customers',
            'view_vendors',
        ])
        ->and($permissionCodesFor('admin_finance'))->toContain(
            'manage_refunds',
            'manage_settlements',
            'reconcile_payments',
            'view_platform_finance',
        )
        ->and(array_intersect(
            ['manage_customer_status', 'suspend_vendors'],
            $permissionCodesFor('admin_finance'),
        ))->toBe([])
        ->and($permissionCodesFor('vendor_owner'))->toHaveCount(20)
        ->and(in_array('access_admin', $permissionCodesFor('vendor_owner'), true))->toBeFalse()
        ->and($permissionCodesFor('vendor_manager'))->toContain('manage_vendor_staff', 'manage_pricing')
        ->and(in_array('view_vendor_finance', $permissionCodesFor('vendor_manager'), true))->toBeFalse()
        ->and($permissionCodesFor('vendor_staff'))->toContain('check_in_bookings', 'complete_bookings')
        ->and(array_intersect(
            ['manage_vendor_staff', 'view_vendor_finance'],
            $permissionCodesFor('vendor_staff'),
        ))->toBe([])
        ->and($permissionCodesFor('vendor_accountant'))->toBe([
            'access_vendor_portal',
            'view_vendor_bookings',
            'view_vendor_finance',
            'view_vendor_reports',
            'view_vendor_settlements',
        ])
        ->and($permissionCodesFor('customer'))->toBe([]);
});

test('permission seeding repairs canonical data and grants without changing custom roles', function () {
    app(DatabaseSeeder::class)->run();

    $managePricing = Permission::query()->where('code', 'manage_pricing')->firstOrFail();
    $managePricing->update([
        'name' => 'Changed Pricing Permission',
        'description' => null,
    ]);

    $customPermission = Permission::query()->create([
        'name' => 'Use External Integration',
        'code' => 'use_external_integration',
        'description' => 'A permission managed outside the canonical seed catalog.',
    ]);
    $customRole = Role::query()->create([
        'name' => 'Custom Integration Role',
        'code' => 'custom_integration',
        'description' => 'A role managed outside the canonical seed catalog.',
    ]);
    $customRole->permissions()->attach($customPermission);

    $vendorStaff = Role::query()->where('code', 'vendor_staff')->firstOrFail();
    $vendorStaff->permissions()->attach($customPermission);

    app(PermissionSeeder::class)->run();

    $reloadedManagePricing = Permission::query()->findOrFail($managePricing->id);

    expect(Permission::query()->count())->toBe(49)
        ->and($reloadedManagePricing->id)->toBe($managePricing->id)
        ->and($reloadedManagePricing->name)->toBe('Manage Pricing')
        ->and($reloadedManagePricing->description)->toBe('Manage turf pricing within the assigned vendor scope.')
        ->and($customRole->permissions()->pluck('code')->all())->toBe(['use_external_integration'])
        ->and($vendorStaff->permissions()->where('code', 'use_external_integration')->exists())->toBeFalse();
});
