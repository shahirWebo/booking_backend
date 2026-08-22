<?php

use App\Models\Vendor;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('availability tables expose recurring rules blocks closures maintenance and turf booking configuration columns', function (): void {
    expect(Schema::hasColumns('turfs', [
        'booking_lead_time_minutes',
        'advance_booking_window_days',
        'default_slot_duration_minutes',
        'min_booking_duration_minutes',
        'max_booking_duration_minutes',
    ]))->toBeTrue();

    expect(Schema::hasColumns('availability_rules', [
        'turf_id',
        'weekday',
        'is_active',
    ]))->toBeTrue();

    expect(Schema::hasColumns('availability_time_ranges', [
        'availability_rule_id',
        'sequence',
        'starts_at_time',
        'ends_at_time',
        'ends_next_day',
    ]))->toBeTrue();

    expect(Schema::hasColumns('slot_blocks', [
        'turf_id',
        'block_date',
        'is_full_day',
        'starts_at_time',
        'ends_at_time',
        'ends_next_day',
        'reason',
    ]))->toBeTrue();

    expect(Schema::hasColumns('holidays', [
        'location_id',
        'holiday_date',
        'name',
        'reason',
        'is_closed',
    ]))->toBeTrue();

    expect(Schema::hasColumns('maintenance_blocks', [
        'turf_id',
        'starts_at',
        'ends_at',
        'reason',
    ]))->toBeTrue();
});

test('turfs enforce availability configuration defaults and duration bounds', function (): void {
    $vendor = Vendor::factory()->create();
    $locationId = DB::table('locations')->insertGetId([
        'vendor_id' => $vendor->id,
        'name' => 'Bellandur Sports Hub',
        'address_line_1' => '11 Ring Road',
        'city' => 'Bengaluru',
        'state' => 'Karnataka',
        'postal_code' => '560103',
        'country_code' => 'IN',
        'timezone' => 'Asia/Kolkata',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $turfId = DB::table('turfs')->insertGetId([
        'location_id' => $locationId,
        'name' => 'Football Arena',
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $config = DB::table('turfs')->where('id', $turfId)->first([
        'booking_lead_time_minutes',
        'advance_booking_window_days',
        'default_slot_duration_minutes',
        'min_booking_duration_minutes',
        'max_booking_duration_minutes',
    ]);

    expect($config)->not->toBeNull()
        ->and($config->booking_lead_time_minutes)->toBe(0)
        ->and($config->advance_booking_window_days)->toBe(30)
        ->and($config->default_slot_duration_minutes)->toBe(60)
        ->and($config->min_booking_duration_minutes)->toBe(60)
        ->and($config->max_booking_duration_minutes)->toBe(240);

    expect(fn () => DB::table('turfs')->insert([
        'location_id' => $locationId,
        'name' => 'Broken Booking Window',
        'status' => 'inactive',
        'advance_booking_window_days' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    expect(fn () => DB::table('turfs')->insert([
        'location_id' => $locationId,
        'name' => 'Broken Duration Bounds',
        'status' => 'inactive',
        'default_slot_duration_minutes' => 30,
        'min_booking_duration_minutes' => 60,
        'max_booking_duration_minutes' => 180,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

test('availability rules support one active weekly schedule per turf weekday and multiple time ranges', function (): void {
    $vendor = Vendor::factory()->create();
    $locationId = DB::table('locations')->insertGetId([
        'vendor_id' => $vendor->id,
        'name' => 'HSR Match Point',
        'address_line_1' => '22 Club Street',
        'city' => 'Bengaluru',
        'state' => 'Karnataka',
        'postal_code' => '560102',
        'country_code' => 'IN',
        'timezone' => 'Asia/Kolkata',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $turfId = DB::table('turfs')->insertGetId([
        'location_id' => $locationId,
        'name' => 'Box Cricket Turf',
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $ruleId = DB::table('availability_rules')->insertGetId([
        'turf_id' => $turfId,
        'weekday' => 1,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('availability_time_ranges')->insert([
        [
            'availability_rule_id' => $ruleId,
            'sequence' => 1,
            'starts_at_time' => '06:00:00',
            'ends_at_time' => '10:00:00',
            'ends_next_day' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'availability_rule_id' => $ruleId,
            'sequence' => 2,
            'starts_at_time' => '18:00:00',
            'ends_at_time' => '23:00:00',
            'ends_next_day' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    expect(DB::table('availability_time_ranges')->where('availability_rule_id', $ruleId)->count())->toBe(2);

    expect(fn () => DB::table('availability_rules')->insert([
        'turf_id' => $turfId,
        'weekday' => 1,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    expect(fn () => DB::table('availability_rules')->insert([
        'turf_id' => $turfId,
        'weekday' => 8,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    expect(fn () => DB::table('availability_time_ranges')->insert([
        'availability_rule_id' => $ruleId,
        'sequence' => 3,
        'starts_at_time' => '10:00:00',
        'ends_at_time' => '08:00:00',
        'ends_next_day' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

test('slot blocks holidays and maintenance blocks enforce valid shapes uniqueness and cascade with ownership', function (): void {
    $vendor = Vendor::factory()->create();
    $locationId = DB::table('locations')->insertGetId([
        'vendor_id' => $vendor->id,
        'name' => 'Jayanagar Courts',
        'address_line_1' => '77 Main Street',
        'city' => 'Bengaluru',
        'state' => 'Karnataka',
        'postal_code' => '560041',
        'country_code' => 'IN',
        'timezone' => 'Asia/Kolkata',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $turfId = DB::table('turfs')->insertGetId([
        'location_id' => $locationId,
        'name' => 'Night Arena',
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('slot_blocks')->insert([
        'turf_id' => $turfId,
        'block_date' => '2026-08-30',
        'is_full_day' => true,
        'reason' => 'Tournament buyout',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('slot_blocks')->insert([
        'turf_id' => $turfId,
        'block_date' => '2026-08-31',
        'is_full_day' => false,
        'starts_at_time' => '22:00:00',
        'ends_at_time' => '01:00:00',
        'ends_next_day' => true,
        'reason' => 'Private scrimmage hold',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('holidays')->insert([
        'location_id' => $locationId,
        'holiday_date' => '2026-10-02',
        'name' => 'Gandhi Jayanti',
        'reason' => 'Location closed for public holiday',
        'is_closed' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('maintenance_blocks')->insert([
        'turf_id' => $turfId,
        'starts_at' => '2026-08-30 18:00:00+00:00',
        'ends_at' => '2026-08-30 20:30:00+00:00',
        'reason' => 'Floodlight repair',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => DB::table('slot_blocks')->insert([
        'turf_id' => $turfId,
        'block_date' => '2026-09-01',
        'is_full_day' => true,
        'starts_at_time' => '08:00:00',
        'ends_at_time' => '10:00:00',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    expect(fn () => DB::table('holidays')->insert([
        'location_id' => $locationId,
        'holiday_date' => '2026-10-02',
        'name' => 'Duplicate holiday',
        'is_closed' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    expect(fn () => DB::table('maintenance_blocks')->insert([
        'turf_id' => $turfId,
        'starts_at' => '2026-08-30 20:30:00+00:00',
        'ends_at' => '2026-08-30 18:00:00+00:00',
        'reason' => 'Broken interval',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    DB::table('locations')->where('id', $locationId)->delete();

    expect(DB::table('holidays')->where('location_id', $locationId)->exists())->toBeFalse()
        ->and(DB::table('turfs')->where('id', $turfId)->exists())->toBeFalse()
        ->and(DB::table('slot_blocks')->where('turf_id', $turfId)->exists())->toBeFalse()
        ->and(DB::table('maintenance_blocks')->where('turf_id', $turfId)->exists())->toBeFalse();
});

test('availability schema provides the documented scheduling and lookup indexes', function (): void {
    $indexesFor = static fn (string $table): array => collect(DB::select("PRAGMA index_list('{$table}')"))
        ->pluck('name')
        ->all();

    expect($indexesFor('turfs'))
        ->toContain('turfs_location_id_default_slot_duration_minutes_index');
    expect($indexesFor('availability_rules'))
        ->toContain('availability_rules_turf_id_weekday_unique')
        ->toContain('availability_rules_turf_id_is_active_index');
    expect($indexesFor('availability_time_ranges'))
        ->toContain('availability_time_ranges_rule_id_sequence_unique')
        ->toContain('availability_time_ranges_rule_id_starts_at_time_index');
    expect($indexesFor('slot_blocks'))
        ->toContain('slot_blocks_turf_id_block_date_index')
        ->toContain('slot_blocks_turf_id_is_full_day_index');
    expect($indexesFor('holidays'))
        ->toContain('holidays_location_id_holiday_date_unique')
        ->toContain('holidays_location_id_is_closed_index');
    expect($indexesFor('maintenance_blocks'))
        ->toContain('maintenance_blocks_turf_id_starts_at_index')
        ->toContain('maintenance_blocks_turf_id_ends_at_index');
});
