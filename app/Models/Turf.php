<?php

namespace App\Models;

use App\Domain\Turfs\Enums\TurfStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $location_id
 * @property string $name
 * @property string|null $description
 * @property TurfStatus $status
 * @property string|null $surface_type
 * @property bool|null $is_indoor
 * @property int|null $capacity_count
 * @property float|null $length_meters
 * @property float|null $width_meters
 * @property int $booking_lead_time_minutes
 * @property int $advance_booking_window_days
 * @property int $default_slot_duration_minutes
 * @property int $min_booking_duration_minutes
 * @property int $max_booking_duration_minutes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Location $location
 */
#[Fillable([
    'location_id',
    'name',
    'description',
    'status',
    'surface_type',
    'is_indoor',
    'capacity_count',
    'length_meters',
    'width_meters',
    'booking_lead_time_minutes',
    'advance_booking_window_days',
    'default_slot_duration_minutes',
    'min_booking_duration_minutes',
    'max_booking_duration_minutes',
])]
class Turf extends Model
{
    protected function casts(): array
    {
        return [
            'status' => TurfStatus::class,
            'is_indoor' => 'boolean',
            'capacity_count' => 'integer',
            'length_meters' => 'float',
            'width_meters' => 'float',
            'booking_lead_time_minutes' => 'integer',
            'advance_booking_window_days' => 'integer',
            'default_slot_duration_minutes' => 'integer',
            'min_booking_duration_minutes' => 'integer',
            'max_booking_duration_minutes' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return BelongsToMany<Sport, $this>
     */
    public function sports(): BelongsToMany
    {
        return $this->belongsToMany(Sport::class, 'turf_sports');
    }

    /**
     * @return BelongsToMany<Amenity, $this>
     */
    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'turf_amenities');
    }

    /**
     * @return HasMany<TurfImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(TurfImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<TurfRule, $this>
     */
    public function rules(): HasMany
    {
        return $this->hasMany(TurfRule::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<AvailabilityRule, $this>
     */
    public function availabilityRules(): HasMany
    {
        return $this->hasMany(AvailabilityRule::class)->orderBy('weekday')->orderBy('id');
    }

    /**
     * @return HasMany<PricingRule, $this>
     */
    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class)->orderBy('priority')->orderBy('id');
    }

    /**
     * @return HasMany<SlotBlock, $this>
     */
    public function slotBlocks(): HasMany
    {
        return $this->hasMany(SlotBlock::class)
            ->orderBy('block_date')
            ->orderBy('is_full_day', 'desc')
            ->orderBy('starts_at_time')
            ->orderBy('id');
    }

    /**
     * @return HasMany<MaintenanceBlock, $this>
     */
    public function maintenanceBlocks(): HasMany
    {
        return $this->hasMany(MaintenanceBlock::class)->orderBy('starts_at')->orderBy('id');
    }
}
