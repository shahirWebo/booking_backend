<?php

use App\Models\Vendor;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('pricing rules table exposes the supported pricing selectors money fields and lifecycle columns', function (): void {
    expect(Schema::hasColumns('pricing_rules', [
        'turf_id',
        'rule_type',
        'price_minor',
        'currency',
        'priority',
        'effective_from_date',
        'effective_until_date',
        'weekday',
        'special_date',
        'starts_at_time',
        'ends_at_time',
        'ends_next_day',
        'is_active',
    ]))->toBeTrue();
});

test('pricing rules support base weekday weekend peak-hour and special-date pricing shapes', function (): void {
    $vendor = Vendor::factory()->create();
    $locationId = DB::table('locations')->insertGetId([
        'vendor_id' => $vendor->id,
        'name' => 'Koramangala Sports Hub',
        'address_line_1' => '90 Arena Road',
        'city' => 'Bengaluru',
        'state' => 'Karnataka',
        'postal_code' => '560095',
        'country_code' => 'IN',
        'timezone' => 'Asia/Kolkata',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $turfId = DB::table('turfs')->insertGetId([
        'location_id' => $locationId,
        'name' => 'Five-a-side Turf',
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $defaultRule = [
        'turf_id' => $turfId,
        'currency' => 'INR',
        'effective_from_date' => null,
        'effective_until_date' => null,
        'weekday' => null,
        'special_date' => null,
        'starts_at_time' => null,
        'ends_at_time' => null,
        'ends_next_day' => false,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ];

    DB::table('pricing_rules')->insert([
        array_merge($defaultRule, [
            'rule_type' => 'base',
            'price_minor' => 180000,
            'priority' => 100,
        ]),
        array_merge($defaultRule, [
            'rule_type' => 'weekday',
            'price_minor' => 160000,
            'priority' => 200,
            'weekday' => 1,
            'effective_from_date' => '2026-09-01',
            'effective_until_date' => '2026-12-31',
        ]),
        array_merge($defaultRule, [
            'rule_type' => 'weekend',
            'price_minor' => 220000,
            'priority' => 300,
        ]),
        array_merge($defaultRule, [
            'rule_type' => 'peak_hour',
            'price_minor' => 260000,
            'priority' => 400,
            'starts_at_time' => '18:00:00',
            'ends_at_time' => '22:00:00',
        ]),
        array_merge($defaultRule, [
            'rule_type' => 'special_date',
            'price_minor' => 300000,
            'priority' => 500,
            'special_date' => '2026-12-25',
        ]),
    ]);

    expect(DB::table('pricing_rules')->where('turf_id', $turfId)->count())->toBe(5);

    expect(fn () => DB::table('pricing_rules')->insert([
        'turf_id' => $turfId,
        'rule_type' => 'weekday',
        'price_minor' => -1,
        'currency' => 'INR',
        'priority' => 100,
        'weekday' => 2,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    expect(fn () => DB::table('pricing_rules')->insert([
        'turf_id' => $turfId,
        'rule_type' => 'weekday',
        'price_minor' => 175000,
        'currency' => 'inr',
        'priority' => 100,
        'weekday' => 2,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    expect(fn () => DB::table('pricing_rules')->insert([
        'turf_id' => $turfId,
        'rule_type' => 'weekend',
        'price_minor' => 210000,
        'currency' => 'INR',
        'priority' => 100,
        'weekday' => 6,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    expect(fn () => DB::table('pricing_rules')->insert([
        'turf_id' => $turfId,
        'rule_type' => 'special_date',
        'price_minor' => 280000,
        'currency' => 'INR',
        'priority' => 100,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    expect(fn () => DB::table('pricing_rules')->insert([
        'turf_id' => $turfId,
        'rule_type' => 'peak_hour',
        'price_minor' => 280000,
        'currency' => 'INR',
        'priority' => 100,
        'starts_at_time' => '21:00:00',
        'ends_at_time' => '18:00:00',
        'ends_next_day' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    expect(fn () => DB::table('pricing_rules')->insert([
        'turf_id' => $turfId,
        'rule_type' => 'base',
        'price_minor' => 180000,
        'currency' => 'INR',
        'priority' => 100,
        'effective_from_date' => '2026-12-31',
        'effective_until_date' => '2026-09-01',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    DB::table('turfs')->where('id', $turfId)->delete();

    expect(DB::table('pricing_rules')->where('turf_id', $turfId)->exists())->toBeFalse();
});

test('pricing schema provides the documented lookup indexes', function (): void {
    $indexesFor = static fn (string $table): array => collect(DB::select("PRAGMA index_list('{$table}')"))
        ->pluck('name')
        ->all();

    expect($indexesFor('pricing_rules'))
        ->toContain('pricing_rules_turf_id_is_active_priority_index')
        ->toContain('pricing_rules_turf_id_rule_type_effective_from_date_index')
        ->toContain('pricing_rules_turf_id_special_date_index');
});
