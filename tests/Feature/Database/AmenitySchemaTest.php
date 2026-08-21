<?php

use App\Models\Amenity;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

test('amenities table stores the display name and stable unique code', function () {
    expect(Schema::hasColumns('amenities', [
        'id',
        'name',
        'code',
        'description',
        'is_active',
        'created_at',
        'updated_at',
    ]))->toBeTrue();

    $amenity = Amenity::query()->create([
        'name' => 'Parking',
        'code' => 'parking',
        'description' => 'Vehicle parking available at the venue.',
        'is_active' => false,
    ]);

    expect($amenity)->toBeInstanceOf(Amenity::class);
    expect($amenity->id)->toBeInt();
    expect($amenity->name)->toBe('Parking');
    expect($amenity->code)->toBe('parking');
    expect($amenity->description)->toBe('Vehicle parking available at the venue.');
    expect($amenity->is_active)->toBeFalse();
});

test('amenities default to active status when a status is not provided', function () {
    $amenity = Amenity::query()->create([
        'name' => 'Locker',
        'code' => 'locker',
    ]);

    expect($amenity->refresh()->is_active)->toBeTrue();
});

test('amenities require unique display names and stable codes', function () {
    Amenity::query()->create([
        'name' => 'Parking',
        'code' => 'parking',
    ]);

    expect(fn () => Amenity::query()->create([
        'name' => 'Parking',
        'code' => 'car_parking',
    ]))->toThrow(QueryException::class);

    expect(fn () => Amenity::query()->create([
        'name' => 'Locker',
        'code' => 'parking',
    ]))->toThrow(QueryException::class);
});
