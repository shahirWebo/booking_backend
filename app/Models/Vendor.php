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
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['status'])]
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
}
