<?php

namespace App\Domain\Auth\Services;

use Illuminate\Contracts\Hashing\Hasher;
use LogicException;

final class OtpCodeHasher
{
    public function __construct(private readonly Hasher $hasher) {}

    public function hash(string $otpRequestId, string $code): string
    {
        return $this->hasher->make($this->payload($otpRequestId, $code));
    }

    public function verify(string $otpRequestId, string $code, string $hash): bool
    {
        return $this->hasher->check($this->payload($otpRequestId, $code), $hash);
    }

    private function payload(string $otpRequestId, string $code): string
    {
        if (! preg_match('/^\d{6}$/', $code)) {
            throw new LogicException('OTP codes must contain exactly six digits.');
        }

        $pepper = config('otp.hash_pepper');

        if (! is_string($pepper) || $pepper === '') {
            throw new LogicException('OTP_HASH_PEPPER must be configured.');
        }

        return $otpRequestId.':'.$code.':'.$pepper;
    }
}
