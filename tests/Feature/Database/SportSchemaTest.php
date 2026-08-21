<?php

use App\Models\Sport;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

test('sports table stores the display name and stable unique code', function () {
    expect(Schema::hasColumns('sports', [
        'id',
        'name',
        'code',
        'description',
        'created_at',
        'updated_at',
    ]))->toBeTrue();

    $sport = Sport::query()->create([
        'name' => 'Football',
        'code' => 'football',
        'description' => 'Association football supported for turf discovery and booking.',
    ]);

    expect($sport)->toBeInstanceOf(Sport::class);
    expect($sport->id)->toBeInt();
    expect($sport->name)->toBe('Football');
    expect($sport->code)->toBe('football');
    expect($sport->description)->toBe(
        'Association football supported for turf discovery and booking.',
    );
});

test('sports require unique display names and stable codes', function () {
    Sport::query()->create([
        'name' => 'Football',
        'code' => 'football',
    ]);

    expect(fn () => Sport::query()->create([
        'name' => 'Football',
        'code' => 'association_football',
    ]))->toThrow(QueryException::class);

    expect(fn () => Sport::query()->create([
        'name' => 'Box Cricket',
        'code' => 'football',
    ]))->toThrow(QueryException::class);
});
