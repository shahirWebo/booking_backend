<?php

use App\Models\Permission;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

test('permissions table stores the display name and stable unique code', function () {
    expect(Schema::hasColumns('permissions', [
        'id',
        'name',
        'code',
        'description',
        'created_at',
        'updated_at',
    ]))->toBeTrue();

    $permission = Permission::query()->create([
        'name' => 'Manage Vendor Bookings',
        'code' => 'vendor_bookings_manage',
        'description' => 'Allows managing bookings for an assigned vendor.',
    ]);

    expect($permission)
        ->id->toBeInt()
        ->name->toBe('Manage Vendor Bookings')
        ->code->toBe('vendor_bookings_manage')
        ->description->toBe('Allows managing bookings for an assigned vendor.');
});

test('permissions require unique display names and stable codes', function () {
    Permission::query()->create([
        'name' => 'Manage Vendor Bookings',
        'code' => 'vendor_bookings_manage',
    ]);

    expect(fn () => Permission::query()->create([
        'name' => 'Manage Vendor Bookings',
        'code' => 'vendor_booking_operations_manage',
    ]))->toThrow(QueryException::class);

    expect(fn () => Permission::query()->create([
        'name' => 'Manage Vendor Booking Operations',
        'code' => 'vendor_bookings_manage',
    ]))->toThrow(QueryException::class);
});
