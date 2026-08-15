<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);

test('an authenticated user can revoke the current bearer token', function () {
    $user = User::factory()->create();
    $currentToken = $user->createToken('current-device');
    $otherToken = $user->createToken('other-device');

    $this->withToken($currentToken->plainTextToken)
        ->deleteJson(route('api.v1.auth.session.destroy'))
        ->assertNoContent();

    expect(PersonalAccessToken::query()->whereKey($currentToken->accessToken->id)->exists())->toBeFalse()
        ->and(PersonalAccessToken::query()->whereKey($otherToken->accessToken->id)->exists())->toBeTrue();
});

test('logout requires an authenticated bearer token', function () {
    $this->deleteJson(route('api.v1.auth.session.destroy'))
        ->assertUnauthorized()
        ->assertJsonPath('success', false)
        ->assertJsonPath('code', 'UNAUTHENTICATED');
});
