<?php

use App\Domain\Users\Enums\UserStatus;
use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an authenticated user can retrieve their customer profile and create it on first access', function () {
    $user = User::factory()->create([
        'name' => 'Asha Patel',
        'mobile_number' => '+919876543210',
        'email' => 'asha@example.test',
        'status' => UserStatus::Active,
    ]);

    $token = $user->createToken('customer-profile-device');

    expect(CustomerProfile::query()->where('user_id', $user->id)->exists())->toBeFalse();

    $this->withToken($token->plainTextToken)
        ->getJson(route('api.v1.customer.profile.show'))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user_id', $user->id)
        ->assertJsonPath('data.name', 'Asha Patel')
        ->assertJsonPath('data.mobile_number', '+919876543210')
        ->assertJsonPath('data.email', 'asha@example.test');

    expect(CustomerProfile::query()->where('user_id', $user->id)->count())->toBe(1);
});

test('an authenticated user retrieves their existing customer profile without duplication', function () {
    $user = User::factory()->create([
        'name' => 'Mira Das',
        'mobile_number' => '+919876543211',
        'email' => 'mira@example.test',
        'status' => UserStatus::Active,
    ]);
    $profile = CustomerProfile::query()->create(['user_id' => $user->id]);
    $token = $user->createToken('customer-profile-device');

    $this->withToken($token->plainTextToken)
        ->getJson(route('api.v1.customer.profile.show'))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $profile->id)
        ->assertJsonPath('data.user_id', $user->id);

    expect(CustomerProfile::query()->where('user_id', $user->id)->count())->toBe(1);
});

test('the customer profile endpoint requires an authenticated bearer token', function () {
    $this->getJson(route('api.v1.customer.profile.show'))
        ->assertUnauthorized()
        ->assertJsonPath('success', false)
        ->assertJsonPath('code', 'UNAUTHENTICATED');
});

test('blocked and suspended users cannot retrieve a customer profile with an existing bearer token', function (UserStatus $status, string $code) {
    $user = User::factory()->create(['status' => UserStatus::Active]);
    $token = $user->createToken('customer-profile-device');
    $user->update(['status' => $status]);

    $this->withToken($token->plainTextToken)
        ->getJson(route('api.v1.customer.profile.show'))
        ->assertForbidden()
        ->assertJsonPath('success', false)
        ->assertJsonPath('code', $code);

    expect($token->accessToken->fresh())->toBeNull();
})->with([
    'blocked user' => [UserStatus::Blocked, 'USER_BLOCKED'],
    'suspended user' => [UserStatus::Suspended, 'USER_SUSPENDED'],
]);
