<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $availability_rule_id
 * @property int $sequence
 * @property string $starts_at_time
 * @property string $ends_at_time
 * @property bool $ends_next_day
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'availability_rule_id',
    'sequence',
    'starts_at_time',
    'ends_at_time',
    'ends_next_day',
])]
class AvailabilityTimeRange extends Model
{
    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'ends_next_day' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<AvailabilityRule, $this>
     */
    public function availabilityRule(): BelongsTo
    {
        return $this->belongsTo(AvailabilityRule::class);
    }
}
