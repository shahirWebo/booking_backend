<?php

namespace App\Domain\Vendors\Enums;

enum VendorDocumentType: string
{
    case IdentityProof = 'identity_proof';
    case BusinessRegistration = 'business_registration';
    case GstRegistration = 'gst_registration';
}
