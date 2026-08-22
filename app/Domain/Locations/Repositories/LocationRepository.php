<?php

namespace App\Domain\Locations\Repositories;

use App\Models\File;
use App\Models\Location;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Collection;

final class LocationRepository
{
    /**
     * @return Collection<int, Location>
     */
    public function listForVendor(Vendor $vendor): Collection
    {
        return Location::query()
            ->where('vendor_id', $vendor->id)
            ->with(['operatingHours', 'amenities', 'images.file'])
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(Vendor $vendor, array $attributes): Location
    {
        /** @var Location $location */
        $location = Location::query()->create([
            ...$attributes,
            'vendor_id' => $vendor->id,
        ]);

        return $location->refresh();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Location $location, array $attributes): Location
    {
        $location->fill($attributes);
        $location->save();

        return $location->refresh();
    }

    /**
     * @param  list<array{weekday: int, opens_at_time: string, closes_at_time: string, ends_next_day: bool}>  $operatingHours
     */
    public function syncOperatingHours(Location $location, array $operatingHours): void
    {
        $location->operatingHours()->delete();

        $sequences = [];

        foreach ($operatingHours as $hour) {
            $weekday = $hour['weekday'];
            $sequences[$weekday] = ($sequences[$weekday] ?? 0) + 1;

            $location->operatingHours()->create([
                'weekday' => $weekday,
                'sequence' => $sequences[$weekday],
                'opens_at_time' => $hour['opens_at_time'],
                'closes_at_time' => $hour['closes_at_time'],
                'ends_next_day' => $hour['ends_next_day'],
            ]);
        }
    }

    /**
     * @param  list<int>  $amenityIds
     */
    public function syncAmenities(Location $location, array $amenityIds): void
    {
        $location->amenities()->sync($amenityIds);
    }

    /**
     * @param  list<array{file: File, caption: string|null, alt_text: string|null}>  $images
     */
    public function syncImages(Location $location, array $images): void
    {
        $location->images()->delete();

        foreach ($images as $index => $image) {
            $location->images()->create([
                'file_id' => $image['file']->id,
                'sort_order' => $index + 1,
                'caption' => $image['caption'],
                'alt_text' => $image['alt_text'],
            ]);
        }
    }
}
