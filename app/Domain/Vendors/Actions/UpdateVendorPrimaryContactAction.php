<?php

namespace App\Domain\Vendors\Actions;

use App\Domain\Vendors\Enums\VendorStatus;
use App\Models\Vendor;
use Illuminate\Validation\ValidationException;

final class UpdateVendorPrimaryContactAction
{
    /**
     * @param  array{primary_contact_name: string, primary_contact_email: string, primary_contact_mobile_number: string}  $contact
     */
    public function execute(Vendor $vendor, array $contact): void
    {
        if ($vendor->status !== VendorStatus::Draft) {
            throw ValidationException::withMessages([
                'vendor' => 'Primary contact details can only be edited while registration is a draft.',
            ]);
        }

        $vendor->fill($contact)->save();
    }
}
