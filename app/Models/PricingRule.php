<?php

namespace App\Models;

use App\Domain\Pricing\Enums\PricingRuleType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $turf_id
 * @property PricingRuleType $rule_type
 * @property int $price_minor
 * @property string $currency
 * @property int $priority
 * @property string|null $effective_from_date
 * @property string|null $effective_until_date
 * @property int|null $weekday
 * @property string|null $special_date
 * @property string|null $starts_at_time
 * @property string|null $ends_at_time
 * @property bool $ends_next_day
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'turf_id',
    'rule_type',
    'price_minor',
    'currency',
    'priority',
    'effective_from_date',
    'effective_until_date',
    'weekday',
    'special_date',
    'starts_at_time',
    'ends_at_time',
    'ends_next_day',
    'is_active',
])]
class PricingRule extends Model
{
    protected function casts(): array
    {
        return [
            'rule_type' => PricingRuleType::class,
            'price_minor' => 'integer',
            'priority' => 'integer',
            'weekday' => 'integer',
            'ends_next_day' => 'boolean',
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
}
