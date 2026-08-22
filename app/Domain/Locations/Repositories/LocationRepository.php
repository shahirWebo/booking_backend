<?php

namespace App\Domain\Locations\Repositories;

use App\Domain\Files\Enums\FilePurpose;
use App\Domain\Files\Enums\FileStatus;
use App\Models\File;
use App\Models\Location;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;
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
     * @return Collection<int, File>
     */
    public function availableImageFiles(Vendor $vendor, ?Location $currentLocation = null): Collection
    {
        return File::query()
            ->where('vendor_id', $vendor->id)
            ->where('purpose', FilePurpose::LocationImage->value)
            ->where('status', FileStatus::Ready->value)
            ->where(function (Builder $query) use ($currentLocation): void {
                $query->whereDoesntHave('locationImages');

                if ($currentLocation instanceof Location) {
                    $query->orWhereHas('locationImages', function (Builder $locationImages) use ($currentLocation): void {
                        $locationImages->where('location_id', $currentLocation->id);
                    });
                }
            })
            ->with(['locationImages' => function ($query) use ($currentLocation): void {
                if ($currentLocation instanceof Location) {
                    $query->where('location_id', $currentLocation->id);
                }
            }])
            ->orderByDesc('ready_at')
            ->orderBy('id')
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
