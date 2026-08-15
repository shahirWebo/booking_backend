<?php

namespace App\Domain\Auth\Data;

enum OtpDeliveryOutcome: string
{
    case Accepted = 'accepted';
    case TransientFailure = 'transient_failure';
    case PermanentFailure = 'permanent_failure';
    case Unknown = 'unknown';
}
