<?php

namespace App\Domain\Auth\Contracts;

use App\Domain\Auth\Data\OtpDeliveryRequest;
use App\Domain\Auth\Data\OtpDeliveryResult;

interface OtpDeliveryProvider
{
    /**
     * Send the platform-approved OTP template.
     *
     * Implementations must not expose provider SDK objects or exceptions to
     * Auth domain callers.
     */
    public function send(OtpDeliveryRequest $request): OtpDeliveryResult;
}
