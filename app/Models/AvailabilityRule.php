<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $turf_id
 * @property int $weekday
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'turf_id',
    'weekday',
    'is_active',
])]
class AvailabilityRule extends Model
{
    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Turf, $this>
     */
    public function turf(): BelongsTo
    {
        return $this->belongsTo(Turf::class);
    }

    /**
     * @return HasMany<AvailabilityTimeRange, $this>
     */
    public function timeRanges(): HasMany
    {
        return $this->hasMany(AvailabilityTimeRange::class)->orderBy('sequence')->orderBy('id');
    }
}
