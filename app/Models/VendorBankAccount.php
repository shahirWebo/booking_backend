<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $vendor_id
 * @property string $account_holder_name
 * @property string $bank_name
 * @property string $account_number_encrypted
 * @property string $account_number_last_four
 * @property string|null $routing_code_encrypted
 * @property string $country_code
 * @property string $currency
 * @property int $submission_version
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Vendor $vendor
 */
#[Fillable([
    'vendor_id',
    'account_holder_name',
    'bank_name',
    'account_number_encrypted',
    'account_number_last_four',
    'routing_code_encrypted',
    'country_code',
    'currency',
    'submission_version',
    'status',
])]
class VendorBankAccount extends Model
{
    /**
     * The vendor that owns this payout account.
     *
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'account_number_encrypted' => 'encrypted',
            'routing_code_encrypted' => 'encrypted',
        ];
    }
}
