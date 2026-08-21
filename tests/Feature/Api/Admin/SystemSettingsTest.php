<?php

use App\Domain\SystemSettings\Services\SystemSettingsService;
use App\Domain\SystemSettings\SystemSettingKey;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\getJson;
use function Pest\Laravel\putJson;
use function Pest\Laravel\withToken;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(DatabaseSeeder::class)->run();
});

test('admin system settings routes require authentication', function () {
    getJson(route('api.v1.admin.system_settings.show'))
        ->assertUnauthorized()
        ->assertJsonPath('code', 'UNAUTHENTICATED');
});

test('admin system settings require the manage system settings permission', function () {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('code', 'admin_support')->firstOrFail());
    $token = $user->createToken('admin-system-settings-support');

    withToken($token->plainTextToken);

    getJson(route('api.v1.admin.system_settings.show'))
        ->assertForbidden()
        ->assertJsonPath('code', 'FORBIDDEN');
});

test('authorized admins can view and update typed system settings', function () {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('code', 'super_admin')->firstOrFail());
    $token = $user->createToken('admin-system-settings-super-admin');

    withToken($token->plainTextToken);

    getJson(route('api.v1.admin.system_settings.show'))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.booking.booking_hold_minutes', 15)
        ->assertJsonPath('data.otp.code_lifetime_seconds', 300)
        ->assertJsonPath('data.support.support_email', 'support@example.test');

    putJson(route('api.v1.admin.system_settings.update'), [
        'booking' => [
            'booking_hold_minutes' => 10,
            'cancellation_cutoff_hours' => 12,
            'max_advance_booking_days' => 45,
            'min_slot_duration_minutes' => 30,
            'max_booking_duration_minutes' => 240,
        ],
        'otp' => [
            'code_lifetime_seconds' => 420,
            'resend_cooldown_seconds' => 90,
            'max_verification_attempts' => 4,
        ],
        'support' => [
            'support_email' => 'ops@example.test',
            'support_phone_e164' => '+919111111111',
            'support_hours' => 'Daily 08:00-22:00',
            'support_timezone' => 'Asia/Kolkata',
        ],
    ])
        ->assertOk()
        ->assertJsonPath('message', 'System settings updated.')
        ->assertJsonPath('data.booking.booking_hold_minutes', 10)
        ->assertJsonPath('data.otp.resend_cooldown_seconds', 90)
        ->assertJsonPath('data.support.support_email', 'ops@example.test');

    expect(SystemSetting::query()->findOrFail(SystemSettingKey::BookingConfiguration->value)->value)
        ->toMatchArray(['booking_hold_minutes' => 10])
        ->and(SystemSetting::query()->findOrFail(SystemSettingKey::OtpConfiguration->value)->value)
        ->toMatchArray(['max_verification_attempts' => 4])
        ->and(SystemSetting::query()->findOrFail(SystemSettingKey::PlatformSupport->value)->value)
        ->toMatchArray(['support_email' => 'ops@example.test']);
});

test('admin system settings validation rejects malformed payloads', function () {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('code', 'super_admin')->firstOrFail());
    $token = $user->createToken('admin-system-settings-validation');

    withToken($token->plainTextToken);

    putJson(route('api.v1.admin.system_settings.update'), [
        'booking' => [
            'booking_hold_minutes' => 0,
            'cancellation_cutoff_hours' => -1,
            'max_advance_booking_days' => 'soon',
            'min_slot_duration_minutes' => 15,
            'max_booking_duration_minutes' => 15,
        ],
        'otp' => [
            'code_lifetime_seconds' => 30,
            'resend_cooldown_seconds' => 10,
            'max_verification_attempts' => 0,
        ],
        'support' => [
            'support_email' => 'invalid-email',
            'support_phone_e164' => '12345',
            'support_hours' => '',
            'support_timezone' => 'Mars/Olympus',
        ],
    ])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'VALIDATION_ERROR')
        ->assertJsonStructure([
            'errors' => [
                'booking.booking_hold_minutes',
                'booking.cancellation_cutoff_hours',
                'booking.max_advance_booking_days',
                'booking.min_slot_duration_minutes',
                'booking.max_booking_duration_minutes',
                'otp.code_lifetime_seconds',
                'otp.resend_cooldown_seconds',
                'otp.max_verification_attempts',
                'support.support_email',
                'support.support_phone_e164',
                'support.support_hours',
                'support.support_timezone',
            ],
        ]);
});

test('system settings service caches the snapshot and invalidates it after updates', function () {
    $service = app(SystemSettingsService::class);

    $initialSnapshot = $service->snapshot();

    expect($initialSnapshot->booking->bookingHoldMinutes)->toBe(15);

    SystemSetting::query()->findOrFail(SystemSettingKey::BookingConfiguration->value)->update([
        'value' => [
            'booking_hold_minutes' => 25,
            'cancellation_cutoff_hours' => 24,
            'max_advance_booking_days' => 30,
            'min_slot_duration_minutes' => 60,
            'max_booking_duration_minutes' => 180,
        ],
    ]);

    expect($service->snapshot()->booking->bookingHoldMinutes)->toBe(15);

    $updatedSnapshot = $service->update([
        'booking' => [
            'booking_hold_minutes' => 20,
            'cancellation_cutoff_hours' => 36,
            'max_advance_booking_days' => 60,
            'min_slot_duration_minutes' => 60,
            'max_booking_duration_minutes' => 300,
        ],
        'otp' => [
            'code_lifetime_seconds' => 360,
            'resend_cooldown_seconds' => 75,
            'max_verification_attempts' => 6,
        ],
        'support' => [
            'support_email' => 'care@example.test',
            'support_phone_e164' => '+919222222222',
            'support_hours' => 'Daily 07:00-23:00',
            'support_timezone' => 'Asia/Kolkata',
        ],
    ]);

    expect($updatedSnapshot->booking->bookingHoldMinutes)->toBe(20)
        ->and($service->snapshot()->booking->bookingHoldMinutes)->toBe(20);
});
