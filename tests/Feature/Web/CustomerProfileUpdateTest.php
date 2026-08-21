<?php

use App\Domain\Users\Enums\UserStatus;
use App\Models\CustomerProfile;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('an authenticated customer can update their customer profile preferences and image', function (): void {
    Storage::fake('public');

    $sport = Sport::query()->create([
        'name' => 'Pickleball',
        'code' => 'pickleball',
        'description' => 'Fast rallies and compact courts.',
        'is_active' => true,
    ]);

    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.test',
        'status' => UserStatus::Active,
    ]);

    $profile = CustomerProfile::query()->create([
        'user_id' => $user->id,
        'sms_notifications_enabled' => false,
    ]);

    $this->actingAs($user)
        ->put(route('customer.profile.update'), [
            'name' => 'New Name',
            'email' => 'new@example.test',
            'profile_image' => UploadedFile::fake()->image('avatar.jpg', 320, 320),
            'preferred_sport_ids' => [$sport->id],
            'default_location_label' => 'Powai',
            'email_notifications_enabled' => '1',
            'sms_notifications_enabled' => '1',
            'marketing_notifications_enabled' => '0',
        ])
        ->assertRedirect(route('customer.profile.show'))
        ->assertSessionHasNoErrors();

    $user->refresh();
    $profile->refresh();

    expect($user->name)->toBe('New Name')
        ->and($user->email)->toBe('new@example.test')
        ->and($user->email_verified_at)->toBeNull()
        ->and($profile->preferred_sport_ids)->toBe([$sport->id])
        ->and($profile->default_location_label)->toBe('Powai')
        ->and($profile->email_notifications_enabled)->toBeTrue()
        ->and($profile->sms_notifications_enabled)->toBeTrue()
        ->and($profile->marketing_notifications_enabled)->toBeFalse()
        ->and($profile->profile_image_path)->not->toBeNull();

    Storage::disk('public')->assertExists($profile->profile_image_path);
});

test('an authenticated customer can submit an account deletion request without deleting their account', function (): void {
    $user = User::factory()->create([
        'status' => UserStatus::Active,
    ]);

    $this->actingAs($user)
        ->post(route('customer.profile.deletion-request'), [
            'reason' => 'Please remove my profile after my active bookings are finished.',
        ])
        ->assertRedirect(route('customer.profile.show'))
        ->assertSessionHasNoErrors();

    $profile = CustomerProfile::query()->where('user_id', $user->id)->sole();

    expect($profile->account_deletion_requested_at)->not->toBeNull()
        ->and($profile->account_deletion_reason)->toBe('Please remove my profile after my active bookings are finished.')
        ->and(User::query()->whereKey($user->id)->exists())->toBeTrue();
});
