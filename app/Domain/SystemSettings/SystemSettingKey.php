<?php

namespace App\Domain\SystemSettings;

enum SystemSettingKey: string
{
    case BookingConfiguration = 'booking_configuration';
    case OtpConfiguration = 'otp_configuration';
    case PlatformSupport = 'platform_support';
}
