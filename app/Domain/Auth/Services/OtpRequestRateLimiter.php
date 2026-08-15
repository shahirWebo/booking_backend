<?php

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Exceptions\OtpRateLimitExceededException;
use App\Domain\Auth\Exceptions\OtpSecurityControlUnavailableException;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

final class OtpRequestRateLimiter
{
    public function __construct(private readonly OtpPrivacyKeyDeriver $keyDeriver) {}

    /**
     * Consume all issuance limits before a challenge is created.
     *
     * @throws OtpRateLimitExceededException
     */
    public function consume(string $mobileNumber, string $purpose, string $ipAddress, ?string $installationId): void
    {
        $scopes = [
            'mobile' => $this->keyDeriver->mobileLookup($mobileNumber, $purpose),
            'ip' => $this->keyDeriver->sourceIp($ipAddress),
        ];

        if ($installationId !== null) {
            $scopes['installation'] = $this->keyDeriver->installation($installationId);
        }

        try {
            foreach ($this->limitsFor($scopes) as $key => $limit) {
                if (RateLimiter::tooManyAttempts($key, $limit['max_attempts'])) {
                    throw new OtpRateLimitExceededException(max(1, RateLimiter::availableIn($key)));
                }
            }

            foreach ($this->limitsFor($scopes) as $key => $limit) {
                RateLimiter::increment($key, $limit['decay_seconds']);
            }
        } catch (OtpRateLimitExceededException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            // OTP issuance must fail closed if its security control plane is unavailable.
            throw new OtpSecurityControlUnavailableException('OTP abuse controls are unavailable.', previous: $exception);
        }
    }

    /**
     * @param  array<string, string>  $scopes
     * @return array<string, array{max_attempts: int, decay_seconds: int}>
     */
    private function limitsFor(array $scopes): array
    {
        $configuredLimits = config('otp.rate_limits');
        $limits = [];

        foreach ($configuredLimits as $name => $limit) {
            [$scope] = explode('_', $name, 2);

            if (! array_key_exists($scope, $scopes)) {
                continue;
            }

            $limits['v1:auth:otp_request:'.$name.':'.$scopes[$scope]] = [
                'max_attempts' => $limit['max_attempts'],
                'decay_seconds' => $limit['decay_seconds'],
            ];
        }

        return $limits;
    }
}
