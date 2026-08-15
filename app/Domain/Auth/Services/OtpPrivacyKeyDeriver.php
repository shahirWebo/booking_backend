<?php

namespace App\Domain\Auth\Services;

use LogicException;

final class OtpPrivacyKeyDeriver
{
    public function mobileLookup(string $mobileNumber, string $purpose): string
    {
        return $this->derive('mobile:'.$purpose.':'.$mobileNumber);
    }

    public function sourceIp(string $ipAddress): string
    {
        return $this->derive('ip:'.$ipAddress);
    }

    public function installation(string $installationId): string
    {
        return $this->derive('installation:'.$installationId);
    }

    private function derive(string $value): string
    {
        $key = config('otp.lookup_hmac_key');

        if (! is_string($key) || $key === '') {
            throw new LogicException('OTP_LOOKUP_HMAC_KEY must be configured.');
        }

        return hash_hmac('sha256', $value, $key);
    }
}
