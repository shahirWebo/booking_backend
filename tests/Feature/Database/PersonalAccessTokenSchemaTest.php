<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);

test('personal access tokens support hashed, scoped, revocable mobile credentials', function () {
    expect(Schema::hasColumns('personal_access_tokens', [
        'id',
        'tokenable_type',
        'tokenable_id',
        'name',
        'token',
        'abilities',
        'last_used_at',
        'expires_at',
        'created_at',
        'updated_at',
    ]))->toBeTrue();

    $user = User::factory()->create();
    $newToken = $user->createToken('customer-mobile', ['customer:access'], now()->addDay());

    $storedToken = PersonalAccessToken::query()->findOrFail($newToken->accessToken->id);

    expect($storedToken)
        ->tokenable->is($user)->toBeTrue()
        ->and($storedToken->token)->toBe(hash('sha256', explode('|', $newToken->plainTextToken, 2)[1]))
        ->and($storedToken->token)->not->toBe($newToken->plainTextToken)
        ->and($storedToken->can('customer:access'))->toBeTrue()
        ->and($storedToken->expires_at)->not->toBeNull();

    $storedToken->delete();

    expect(PersonalAccessToken::query()->find($storedToken->id))->toBeNull();
});
