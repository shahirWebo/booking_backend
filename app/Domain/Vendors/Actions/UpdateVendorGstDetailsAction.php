<?php

namespace App\Domain\Vendors\Actions;

use App\Domain\Vendors\Enums\VendorStatus;
use App\Models\Vendor;
use Illuminate\Validation\ValidationException;

final class UpdateVendorGstDetailsAction
{
    /**
     * @param  array{is_gst_registered: bool, gstin: string|null}  $details
     */
    public function execute(Vendor $vendor, array $details): void
    {
        if ($vendor->status !== VendorStatus::Draft) {
            throw ValidationException::withMessages([
                'vendor' => 'GST details can only be edited while registration is a draft.',
            ]);
        }

        $vendor->fill($details)->save();
    }
}
