<?php

namespace App\Domain\Vendors\Actions;

use App\Domain\Vendors\Enums\VendorStatus;
use App\Models\Vendor;
use App\Models\VendorBankAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class StoreVendorBankAccountAction
{
    /**
     * @param  array{account_holder_name: string, bank_name: string, account_number: string, routing_code: string}  $details
     */
    public function execute(Vendor $vendor, array $details): VendorBankAccount
    {
        return DB::transaction(function () use ($vendor, $details): VendorBankAccount {
            $vendor = Vendor::query()->lockForUpdate()->findOrFail($vendor->id);

            if ($vendor->status !== VendorStatus::Draft) {
                throw ValidationException::withMessages([
                    'vendor' => 'Bank account details can only be edited while registration is a draft.',
                ]);
            }

            $exists = VendorBankAccount::query()
                ->where('vendor_id', $vendor->id)
                ->where('submission_version', $vendor->submission_version)
                ->where('status', 'active')
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'bank_account' => 'A bank account is already attached to the current submission.',
                ]);
            }

            return VendorBankAccount::query()->create([
                'vendor_id' => $vendor->id,
                'account_holder_name' => $details['account_holder_name'],
                'bank_name' => $details['bank_name'],
                'account_number_encrypted' => $details['account_number'],
                'account_number_last_four' => substr($details['account_number'], -4),
                'routing_code_encrypted' => $details['routing_code'],
                'country_code' => 'IN',
                'currency' => 'INR',
                'submission_version' => $vendor->submission_version,
                'status' => 'active',
            ]);
        });
    }
}
