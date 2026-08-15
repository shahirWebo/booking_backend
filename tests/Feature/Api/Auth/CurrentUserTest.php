<?php

use App\Domain\Users\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an authenticated user can retrieve their profile', function () {
    $user = User::factory()->create([
        'name' => 'Asha Patel',
        'mobile_number' => '+919876543210',
        'email' => 'asha@example.test',
        'status' => UserStatus::Active,
    ]);

    $token = $user->createToken('current-device');

    $this->withToken($token->plainTextToken)
        ->getJson(route('api.v1.auth.user.show'))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data', [
            'id' => $user->id,
            'name' => 'Asha Patel',
            'mobile_number' => '+919876543210',
            'email' => 'asha@example.test',
            'status' => 'active',
        ])
        ->assertJsonMissingPath('data.password')
        ->assertJsonMissingPath('data.two_factor_secret')
        ->assertJsonMissingPath('data.remember_token');
});

test('the current-user profile endpoint requires an authenticated bearer token', function () {
    $this->getJson(route('api.v1.auth.user.show'))
        ->assertUnauthorized()
        ->assertJsonPath('success', false)
        ->assertJsonPath('code', 'UNAUTHENTICATED');
});
