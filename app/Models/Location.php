<?php

namespace App\Models;

use App\Domain\Locations\Enums\LocationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $vendor_id
 * @property string $name
 * @property string $address_line_1
 * @property string|null $address_line_2
 * @property string|null $landmark
 * @property string|null $locality
 * @property string $city
 * @property string $state
 * @property string $postal_code
 * @property string $country_code
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string $timezone
 * @property LocationStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Vendor $vendor
 */
#[Fillable([
    'vendor_id',
    'name',
    'address_line_1',
    'address_line_2',
    'landmark',
    'locality',
    'city',
    'state',
    'postal_code',
    'country_code',
    'latitude',
    'longitude',
    'timezone',
    'status',
])]
class Location extends Model
{
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'status' => LocationStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * @return HasMany<LocationOperatingHour, $this>
     */
    public function operatingHours(): HasMany
    {
        return $this->hasMany(LocationOperatingHour::class)->orderBy('weekday')->orderBy('sequence');
    }

    /**
     * @return BelongsToMany<Amenity, $this>
     */
    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'location_amenities');
    }

    /**
     * @return HasMany<LocationImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(LocationImage::class)->orderBy('sort_order')->orderBy('id');
    }
}
