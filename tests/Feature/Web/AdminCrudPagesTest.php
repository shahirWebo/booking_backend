<?php

use App\Domain\SystemSettings\SystemSettingKey;
use App\Models\Amenity;
use App\Models\Role;
use App\Models\Sport;
use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(DatabaseSeeder::class)->run();
});

test('admin crud pages require the matching browser permission', function (): void {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('code', 'admin_support')->firstOrFail());

    $this->actingAs($user)
        ->get(route('admin.sports.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.amenities.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.system_settings.show'))
        ->assertForbidden();
});

test('admin operators can view sports and amenities inertia crud pages', function (): void {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('code', 'admin_operations')->firstOrFail());

    $this->actingAs($user)
        ->get(route('admin.sports.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/operations/SportsIndex')
            ->has('sports', 5)
            ->where('routes.create', route('admin.sports.create')),
        );

    $this->actingAs($user)
        ->get(route('admin.amenities.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/operations/AmenitiesIndex')
            ->has('amenities', 6)
            ->where('routes.create', route('admin.amenities.create')),
        );
});

test('super admins can view the system settings inertia page', function (): void {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('code', 'super_admin')->firstOrFail());

    $this->actingAs($user)
        ->get(route('admin.system_settings.show'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/governance/SystemSettings')
            ->where('settings.booking.booking_hold_minutes', 15)
            ->where('settings.otp.code_lifetime_seconds', 300)
            ->where('settings.support.support_email', 'support@example.test'),
        );
});

test('admin create and edit pages use dedicated form components', function (): void {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('code', 'admin_operations')->firstOrFail());

    $sport = Sport::query()->where('code', 'football')->sole();
    $amenity = Amenity::query()->where('code', 'parking')->sole();

    $this->actingAs($user)
        ->get(route('admin.sports.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/operations/SportForm')
            ->where('mode', 'create')
            ->where('sport', null),
        );

    $this->actingAs($user)
        ->get(route('admin.sports.edit', $sport))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/operations/SportForm')
            ->where('mode', 'edit')
            ->where('sport.id', $sport->id),
        );

    $this->actingAs($user)
        ->get(route('admin.amenities.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/operations/AmenityForm')
            ->where('mode', 'create')
            ->where('amenity', null),
        );

    $this->actingAs($user)
        ->get(route('admin.amenities.edit', $amenity))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/operations/AmenityForm')
            ->where('mode', 'edit')
            ->where('amenity.id', $amenity->id),
        );
});

test('admin operators can create update and delete sports from the browser flow', function (): void {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('code', 'admin_operations')->firstOrFail());

    $createResponse = $this->actingAs($user)
        ->post(route('admin.sports.store'), [
            'name' => 'Volleyball',
            'code' => 'volleyball',
            'description' => 'Indoor and outdoor volleyball court bookings.',
            'is_active' => false,
            'icon_asset_key' => 'sports/icons/volleyball.png',
            'icon_alt_text' => 'Volleyball sport icon',
            'image_asset_key' => 'sports/images/volleyball.png',
            'image_alt_text' => 'Volleyball sport image',
        ]);

    $createResponse->assertRedirect(route('admin.sports.index'));

    $sport = Sport::query()->where('code', 'volleyball')->sole();

    expect($sport->name)->toBe('Volleyball')
        ->and($sport->is_active)->toBeFalse();

    $this->actingAs($user)
        ->put(route('admin.sports.update', $sport), [
            'name' => 'Pickleball',
            'code' => 'pickleball',
            'description' => 'Pickleball court bookings.',
            'is_active' => true,
            'icon_asset_key' => 'sports/icons/pickleball.png',
            'icon_alt_text' => 'Pickleball sport icon',
            'image_asset_key' => 'sports/images/pickleball.png',
            'image_alt_text' => 'Pickleball sport image',
        ])
        ->assertRedirect(route('admin.sports.index'));

    expect($sport->fresh())
        ->not->toBeNull()
        ->and($sport->fresh()?->name)->toBe('Pickleball')
        ->and($sport->fresh()?->is_active)->toBeTrue();

    $this->actingAs($user)
        ->delete(route('admin.sports.destroy', $sport))
        ->assertRedirect(route('admin.sports.index'));

    expect(Sport::query()->whereKey($sport->id)->exists())->toBeFalse();
});

test('admin operators can create update and delete amenities from the browser flow', function (): void {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('code', 'admin_operations')->firstOrFail());

    $this->actingAs($user)
        ->post(route('admin.amenities.store'), [
            'name' => 'Drinking Water',
            'code' => 'drinking_water',
            'description' => 'Filtered drinking water is available on-site.',
            'is_active' => false,
        ])
        ->assertRedirect(route('admin.amenities.index'));

    $amenity = Amenity::query()->where('code', 'drinking_water')->sole();

    expect($amenity->name)->toBe('Drinking Water')
        ->and($amenity->is_active)->toBeFalse();

    $this->actingAs($user)
        ->put(route('admin.amenities.update', $amenity), [
            'name' => 'Locker Room',
            'code' => 'locker_room',
            'description' => 'Secure locker-room storage for players.',
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.amenities.index'));

    expect($amenity->fresh())
        ->not->toBeNull()
        ->and($amenity->fresh()?->name)->toBe('Locker Room')
        ->and($amenity->fresh()?->is_active)->toBeTrue();

    $this->actingAs($user)
        ->delete(route('admin.amenities.destroy', $amenity))
        ->assertRedirect(route('admin.amenities.index'));

    expect(Amenity::query()->whereKey($amenity->id)->exists())->toBeFalse();
});

test('super admins can update system settings from the browser flow', function (): void {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('code', 'super_admin')->firstOrFail());

    $this->actingAs($user)
        ->put(route('admin.system_settings.update'), [
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
        ->assertRedirect(route('admin.system_settings.show'));

    expect(SystemSetting::query()->findOrFail(SystemSettingKey::BookingConfiguration->value)->value)
        ->toMatchArray(['booking_hold_minutes' => 10])
        ->and(SystemSetting::query()->findOrFail(SystemSettingKey::OtpConfiguration->value)->value)
        ->toMatchArray(['max_verification_attempts' => 4])
        ->and(SystemSetting::query()->findOrFail(SystemSettingKey::PlatformSupport->value)->value)
        ->toMatchArray(['support_email' => 'ops@example.test']);
});
