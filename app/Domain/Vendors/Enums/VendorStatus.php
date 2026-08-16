<?php

namespace App\Domain\Vendors\Enums;

enum VendorStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Suspended = 'suspended';
    case Inactive = 'inactive';
}
