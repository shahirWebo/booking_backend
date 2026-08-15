<?php

namespace App\Domain\Auth\Services;

use App\Models\OtpRequest;
use App\Models\User;
use Laravel\Sanctum\NewAccessToken;

final class OtpAuthenticationService
{
    public function __construct(private readonly OtpChallengeVerifier $challengeVerifier) {}

    /**
     * Verify an authentication challenge and atomically establish an API credential.
     *
     * @return array{user: User, accessToken: NewAccessToken}
     */
    public function authenticate(string $challengeId, string $code): array
    {
        $result = null;

        $this->challengeVerifier->verify($challengeId, $code, function (OtpRequest $challenge) use (&$result): void {
            $user = User::query()->firstOrCreate([
                'mobile_number' => $challenge->mobile_number_ciphertext,
            ]);

            $result = [
                'user' => $user,
                'accessToken' => $user->createToken('otp-authentication', ['api:access']),
            ];
        });

        if (! is_array($result)) {
            throw new \LogicException('A verified OTP challenge must establish authentication.');
        }

        return $result;
    }
}
