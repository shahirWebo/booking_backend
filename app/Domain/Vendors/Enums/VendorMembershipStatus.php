<?php

namespace App\Domain\Vendors\Enums;

enum VendorMembershipStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
