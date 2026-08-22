<?php

namespace App\Domain\Locations\Actions;

use App\Domain\Locations\Repositories\LocationRepository;
use App\Models\File;
use App\Models\Location;
use Illuminate\Support\Facades\DB;

final class UpdateVendorLocationAction
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
        Location $location,
        array $attributes,
        array $operatingHours,
        array $amenityIds,
        array $images,
    ): Location {
        DB::transaction(function () use ($location, $attributes, $operatingHours, $amenityIds, $images): void {
            $this->locations->update($location, $attributes);
            $this->locations->syncOperatingHours($location, $operatingHours);
            $this->locations->syncAmenities($location, $amenityIds);
            $this->locations->syncImages($location, $images);
        });

        return $location->refresh()->load(['operatingHours', 'amenities', 'images.file']);
    }
}
