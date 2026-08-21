<?php

namespace App\Models;

use App\Domain\Vendors\Enums\VendorStatus;
use Database\Factories\VendorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property VendorStatus $status
 * @property string|null $legal_name
 * @property string|null $display_name
 * @property string|null $legal_entity_type
 * @property string|null $primary_contact_name
 * @property string|null $primary_contact_email
 * @property string|null $primary_contact_mobile_number
 * @property int $submission_version
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'status',
    'legal_name',
    'display_name',
    'legal_entity_type',
    'primary_contact_name',
    'primary_contact_email',
    'primary_contact_mobile_number',
    'submission_version',
])]
class Vendor extends Model
{
    /** @use HasFactory<VendorFactory> */
    use HasFactory;

    protected $casts = [
        'status' => VendorStatus::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The staff members (owner, managers, staff) who belong to this vendor.
     *
     * @return HasMany<VendorMembership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(VendorMembership::class);
    }

    /**
     * The users who are active members of this vendor.
     *
     * @return HasMany<VendorMembership, $this>
     */
    public function activeMembers(): HasMany
    {
        return $this->memberships()->where('status', 'active');
    }

    /**
     * The private KYC and business-document attachments supplied by this vendor.
     *
     * @return HasMany<VendorDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(VendorDocument::class);
    }

    /**
     * The private payout accounts supplied by this vendor.
     *
     * @return HasMany<VendorBankAccount, $this>
     */
    public function bankAccounts(): HasMany
    {
        return $this->hasMany(VendorBankAccount::class);
    }

    /**
     * Append-only lifecycle transitions for this vendor.
     *
     * @return HasMany<VendorStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(VendorStatusHistory::class);
    }
}
