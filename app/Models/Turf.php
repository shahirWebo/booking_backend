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
}
