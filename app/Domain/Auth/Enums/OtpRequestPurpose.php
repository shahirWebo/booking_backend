<?php

namespace App\Domain\Auth\Enums;

enum OtpRequestPurpose: string
{
    case Authentication = 'authentication';
}
