<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $location_id
 * @property int $weekday
 * @property int $sequence
 * @property string $opens_at_time
 * @property string $closes_at_time
 * @property bool $ends_next_day
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'location_id',
    'weekday',
    'sequence',
    'opens_at_time',
    'closes_at_time',
    'ends_next_day',
])]
class LocationOperatingHour extends Model
{
    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'sequence' => 'integer',
            'ends_next_day' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
