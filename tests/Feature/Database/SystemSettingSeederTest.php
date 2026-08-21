<?php

use App\Models\SystemSetting;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SystemSettingSeeder;

test('database seeding creates the canonical system settings payloads', function () {
    app(DatabaseSeeder::class)->run();

    expect(SystemSetting::query()->orderBy('key')->get()->mapWithKeys(
        static fn (SystemSetting $setting): array => [$setting->key => $setting->value],
    )->all())->toBe([
        'booking_configuration' => [
            'booking_hold_minutes' => 15,
            'cancellation_cutoff_hours' => 24,
            'max_advance_booking_days' => 30,
            'min_slot_duration_minutes' => 60,
            'max_booking_duration_minutes' => 180,
        ],
        'otp_configuration' => [
            'code_lifetime_seconds' => 300,
            'resend_cooldown_seconds' => 60,
            'max_verification_attempts' => 5,
        ],
        'platform_support' => [
            'support_email' => 'support@example.test',
            'support_phone_e164' => '+919000000000',
            'support_hours' => 'Mon-Sat 09:00-18:00',
            'support_timezone' => 'Asia/Kolkata',
        ],
    ]);
});

test('system settings seeding is repeatable and restores canonical values', function () {
    app(SystemSettingSeeder::class)->run();

    $support = SystemSetting::query()->findOrFail('platform_support');
    $support->update([
        'value' => [
            'support_email' => 'custom@example.test',
            'support_phone_e164' => '+919999999999',
            'support_hours' => '24x7',
            'support_timezone' => 'UTC',
        ],
    ]);

    app(SystemSettingSeeder::class)->run();

    expect(SystemSetting::query()->count())->toBe(3)
        ->and($support->fresh()?->value)->toBe([
            'support_email' => 'support@example.test',
            'support_phone_e164' => '+919000000000',
            'support_hours' => 'Mon-Sat 09:00-18:00',
            'support_timezone' => 'Asia/Kolkata',
        ]);
});
