<?php

use App\Domain\Users\Enums\UserStatus;
use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('the customer profile page redirects guests to login', function (): void {
    $this->get(route('customer.profile.show'))
        ->assertRedirect(route('login'));
});

test('an authenticated user can view their customer profile page and create it on first access', function (): void {
    $user = User::factory()->create([
        'name' => 'Asha Patel',
        'mobile_number' => '+919876543210',
        'email' => 'asha@example.test',
        'status' => UserStatus::Active,
    ]);

    expect(CustomerProfile::query()->where('user_id', $user->id)->exists())->toBeFalse();

    $this->actingAs($user)
        ->get(route('customer.profile.show'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/Profile')
            ->where('profile.user_id', $user->id)
            ->where('profile.name', 'Asha Patel')
            ->where('profile.mobile_number', '+919876543210')
            ->where('profile.email', 'asha@example.test'),
        );

    expect(CustomerProfile::query()->where('user_id', $user->id)->count())->toBe(1);
});

test('an authenticated user reuses their existing customer profile page record', function (): void {
    $user = User::factory()->create([
        'status' => UserStatus::Active,
    ]);
    $profile = CustomerProfile::query()->create([
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('customer.profile.show'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/Profile')
            ->where('profile.id', $profile->id),
        );

    expect(CustomerProfile::query()->where('user_id', $user->id)->count())->toBe(1);
});
