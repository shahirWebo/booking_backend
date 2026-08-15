<?php

namespace App\Domain\Auth\Enums;

enum OtpRequestStatus: string
{
    case PendingDelivery = 'pending_delivery';
    case ProviderAccepted = 'provider_accepted';
    case DeliveryFailed = 'delivery_failed';
    case DeliveryUnknown = 'delivery_unknown';
    case Verified = 'verified';
    case Expired = 'expired';
    case Superseded = 'superseded';
    case AttemptLimitExhausted = 'attempt_limit_exhausted';
}
