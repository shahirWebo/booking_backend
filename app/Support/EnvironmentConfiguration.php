<?php

namespace App\Support;

use LogicException;

final class EnvironmentConfiguration
{
    /**
     * Reject unsafe application settings before serving a deployed environment.
     */
    public static function assertSafe(string $environment, bool $debug): void
    {
        if (in_array($environment, ['staging', 'production'], true) && $debug) {
            throw new LogicException("APP_DEBUG must be false in the {$environment} environment.");
        }
    }

    public static function assertOtpHashPepper(string $environment, mixed $pepper): void
    {
        if (in_array($environment, ['staging', 'production'], true)
            && (! is_string($pepper) || $pepper === '')) {
            throw new LogicException("OTP_HASH_PEPPER must be configured in the {$environment} environment.");
        }
    }

    public static function assertOtpLookupHmacKey(string $environment, mixed $key): void
    {
        if (in_array($environment, ['staging', 'production'], true)
            && (! is_string($key) || $key === '')) {
            throw new LogicException("OTP_LOOKUP_HMAC_KEY must be configured in the {$environment} environment.");
        }
    }
}
