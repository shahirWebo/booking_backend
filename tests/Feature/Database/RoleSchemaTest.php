<?php

use App\Models\Role;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

test('roles table stores the display name and stable unique code', function () {
    expect(Schema::hasColumns('roles', [
        'id',
        'name',
        'code',
        'description',
        'created_at',
        'updated_at',
    ]))->toBeTrue();

    $role = Role::query()->create([
        'name' => 'Vendor Manager',
        'code' => 'vendor_manager',
        'description' => 'Manages a vendor catalogue and bookings.',
    ]);

    expect($role)
        ->id->toBeInt()
        ->name->toBe('Vendor Manager')
        ->code->toBe('vendor_manager')
        ->description->toBe('Manages a vendor catalogue and bookings.');
});

test('roles require unique display names and stable codes', function () {
    Role::query()->create([
        'name' => 'Vendor Manager',
        'code' => 'vendor_manager',
    ]);

    expect(fn () => Role::query()->create([
        'name' => 'Vendor Manager',
        'code' => 'vendor_operations_manager',
    ]))->toThrow(QueryException::class);

    expect(fn () => Role::query()->create([
        'name' => 'Vendor Operations Manager',
        'code' => 'vendor_manager',
    ]))->toThrow(QueryException::class);
});
