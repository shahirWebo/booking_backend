<?php

use App\Domain\SystemSettings\SystemSettingKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('system settings table stores keyed json configuration payloads', function () {
    expect(Schema::hasColumns('system_settings', [
        'key',
        'value',
        'created_at',
        'updated_at',
    ]))->toBeTrue();

    DB::table('system_settings')->insert([
        'key' => SystemSettingKey::BookingConfiguration->value,
        'value' => json_encode([
            'booking_hold_minutes' => 15,
            'cancellation_cutoff_hours' => 24,
            'max_advance_booking_days' => 30,
            'min_slot_duration_minutes' => 60,
            'max_booking_duration_minutes' => 180,
        ], JSON_THROW_ON_ERROR),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(DB::table('system_settings')->where('key', SystemSettingKey::BookingConfiguration->value)->exists())->toBeTrue();
});
