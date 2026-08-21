<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $vendor_id
 * @property int|null $actor_user_id
 * @property int $sequence
 * @property string|null $from_status
 * @property string $to_status
 * @property string|null $reason_code
 * @property string|null $reason_message
 * @property string|null $correlation_id
 * @property Carbon $transitioned_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $actor
 * @property-read Vendor $vendor
 */
#[Fillable([
    'vendor_id',
    'actor_user_id',
    'sequence',
    'from_status',
    'to_status',
    'reason_code',
    'reason_message',
    'correlation_id',
    'transitioned_at',
])]
class VendorStatusHistory extends Model
{
    /**
     * The vendor whose transition was recorded.
     *
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * The authenticated actor that initiated this transition.
     *
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'transitioned_at' => 'immutable_datetime',
        ];
    }
}
