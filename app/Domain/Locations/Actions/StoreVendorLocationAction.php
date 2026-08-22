<?php

namespace App\Domain\Locations\Actions;

use App\Domain\Locations\Repositories\LocationRepository;
use App\Models\File;
use App\Models\Location;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;

final class StoreVendorLocationAction
{
    public function __construct(
        private readonly LocationRepository $locations,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<array{weekday: int, opens_at_time: string, closes_at_time: string, ends_next_day: bool}>  $operatingHours
     * @param  list<int>  $amenityIds
     * @param  list<array{file: File, caption: string|null, alt_text: string|null}>  $images
     */
    public function execute(
        Vendor $vendor,
        array $attributes,
        array $operatingHours,
        array $amenityIds,
        array $images,
    ): Location {
        /** @var Location $location */
        $location = DB::transaction(function () use ($vendor, $attributes, $operatingHours, $amenityIds, $images): Location {
            $location = $this->locations->create($vendor, $attributes);
            $this->locations->syncOperatingHours($location, $operatingHours);
            $this->locations->syncAmenities($location, $amenityIds);
            $this->locations->syncImages($location, $images);

            return $location;
        });

        return $location->load(['operatingHours', 'amenities', 'images.file']);
    }
}
