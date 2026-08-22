<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $turf_id
 * @property string $block_date
 * @property bool $is_full_day
 * @property string|null $starts_at_time
 * @property string|null $ends_at_time
 * @property bool $ends_next_day
 * @property string|null $reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'turf_id',
    'block_date',
    'is_full_day',
    'starts_at_time',
    'ends_at_time',
    'ends_next_day',
    'reason',
])]
class SlotBlock extends Model
{
    protected function casts(): array
    {
        return [
            'is_full_day' => 'boolean',
            'ends_next_day' => 'boolean',
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
