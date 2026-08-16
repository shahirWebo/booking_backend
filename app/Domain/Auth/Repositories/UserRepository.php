<?php

namespace App\Domain\Auth\Repositories;

use App\Models\User;
use Laravel\Sanctum\NewAccessToken;

final class UserRepository
{
    public function findOrCreateByMobile(string $mobileNumber): User
    {
        $user = User::query()->firstOrCreate([
            'mobile_number' => $mobileNumber,
        ]);
        $user->refresh();

        return $user;
    }

    public function createAccessToken(User $user): NewAccessToken
    {
        return $user->createToken('otp-authentication', ['api:access']);
    }

    public function revokeCurrentAccessToken(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
