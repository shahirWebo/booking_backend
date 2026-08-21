<?php

namespace Database\Seeders;

use App\Domain\SystemSettings\SystemSettingKey;
use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    /**
     * Seed the canonical non-secret platform settings.
     */
    public function run(): void
    {
        $settings = [
            SystemSettingKey::BookingConfiguration->value => [
                'booking_hold_minutes' => 15,
                'cancellation_cutoff_hours' => 24,
                'max_advance_booking_days' => 30,
                'min_slot_duration_minutes' => 60,
                'max_booking_duration_minutes' => 180,
            ],
            SystemSettingKey::OtpConfiguration->value => [
                'code_lifetime_seconds' => (int) config('otp.code_lifetime_seconds'),
                'resend_cooldown_seconds' => (int) config('otp.resend_cooldown_seconds'),
                'max_verification_attempts' => (int) config('otp.max_verification_attempts'),
            ],
            SystemSettingKey::PlatformSupport->value => [
                'support_email' => 'support@example.test',
                'support_phone_e164' => '+919000000000',
                'support_hours' => 'Mon-Sat 09:00-18:00',
                'support_timezone' => 'Asia/Kolkata',
            ],
        ];

        foreach ($settings as $key => $value) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }
    }
}
