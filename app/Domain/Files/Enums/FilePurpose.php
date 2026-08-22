<?php

namespace App\Domain\Files\Enums;

enum FilePurpose: string
{
    case LocationImage = 'location_image';
    case VendorKycDocument = 'vendor_kyc_document';
}
