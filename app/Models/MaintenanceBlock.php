<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $turf_id
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property string|null $reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'turf_id',
    'starts_at',
    'ends_at',
    'reason',
])]
class MaintenanceBlock extends Model
{
    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Turf, $this>
     */
    public function turf(): BelongsTo
    {
        return $this->belongsTo(Turf::class);
    }
}
