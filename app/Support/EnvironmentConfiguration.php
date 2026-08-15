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
}
