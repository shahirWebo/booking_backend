<?php

namespace App\Domain\Auth\Exceptions;

use RuntimeException;

final class OtpRateLimitExceededException extends RuntimeException
{
    public function __construct(public readonly int $retryAfterSeconds, ?\Throwable $previous = null)
    {
        parent::__construct('OTP request rate limit exceeded.', previous: $previous);
    }
}
