<?php

namespace App\Domain\Auth\Exceptions;

use RuntimeException;

final class OtpResendCooldownException extends RuntimeException
{
    public function __construct(public readonly int $retryAfterSeconds)
    {
        parent::__construct('An OTP was requested too recently.');
    }
}
