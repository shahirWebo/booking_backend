<?php

namespace App\Models;

use App\Domain\Vendors\Enums\VendorMembershipStatus;
use Database\Factories\VendorMembershipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $vendor_id
 * @property int $user_id
 * @property string $role
 * @property VendorMembershipStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Vendor $vendor
 * @property-read User $user
 */
#[Fillable(['vendor_id', 'user_id', 'role', 'status'])]
class VendorMembership extends Model
{
    /** @use HasFactory<VendorMembershipFactory> */
    use HasFactory;

    protected $casts = [
        'status' => VendorMembershipStatus::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The vendor this membership belongs to.
     *
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * The user who has this vendor membership.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this membership is active.
     */
    public function isActive(): bool
    {
        return $this->status === VendorMembershipStatus::Active;
    }
}
