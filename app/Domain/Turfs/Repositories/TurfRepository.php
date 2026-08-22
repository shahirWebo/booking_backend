<?php

namespace App\Domain\Turfs\Repositories;

use App\Domain\Files\Enums\FilePurpose;
use App\Domain\Files\Enums\FileStatus;
use App\Models\File;
use App\Models\Location;
use App\Models\Turf;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class TurfRepository
{
    /**
     * @return Collection<int, Turf>
     */
    public function listForLocation(Location $location): Collection
    {
        return Turf::query()
            ->where('location_id', $location->id)
            ->with(['location.vendor', 'sports', 'amenities', 'images.file', 'rules'])
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, File>
     */
    public function availableImageFiles(Vendor $vendor, ?Turf $currentTurf = null): Collection
    {
        return File::query()
            ->where('vendor_id', $vendor->id)
            ->where('purpose', FilePurpose::TurfImage->value)
            ->where('status', FileStatus::Ready->value)
            ->where(function (Builder $query) use ($currentTurf): void {
                $query->whereDoesntHave('turfImages');

                if ($currentTurf instanceof Turf) {
                    $query->orWhereHas('turfImages', function (Builder $turfImages) use ($currentTurf): void {
                        $turfImages->where('turf_id', $currentTurf->id);
                    });
                }
            })
            ->with(['turfImages' => function ($query) use ($currentTurf): void {
                if ($currentTurf instanceof Turf) {
                    $query->where('turf_id', $currentTurf->id);
                }
            }])
            ->orderByDesc('ready_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(Location $location, array $attributes): Turf
    {
        /** @var Turf $turf */
        $turf = Turf::query()->create([
            ...$attributes,
            'location_id' => $location->id,
        ]);

        return $turf->refresh();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Turf $turf, array $attributes): Turf
    {
        $turf->fill($attributes);
        $turf->save();

        return $turf->refresh();
    }

    /**
     * @param  list<int>  $sportIds
     */
    public function syncSports(Turf $turf, array $sportIds): void
    {
        $turf->sports()->sync($sportIds);
    }

    /**
     * @param  list<int>  $amenityIds
     */
    public function syncAmenities(Turf $turf, array $amenityIds): void
    {
        $turf->amenities()->sync($amenityIds);
    }

    /**
     * @param  list<array{file: File, caption: string|null, alt_text: string|null}>  $images
     */
    public function syncImages(Turf $turf, array $images): void
    {
        $turf->images()->delete();

        foreach ($images as $index => $image) {
            $turf->images()->create([
                'file_id' => $image['file']->id,
                'sort_order' => $index + 1,
                'caption' => $image['caption'],
                'alt_text' => $image['alt_text'],
            ]);
        }
    }

    /**
     * @param  list<array{title: string, description: string, is_active: bool}>  $rules
     */
    public function syncRules(Turf $turf, array $rules): void
    {
        $turf->rules()->delete();

        foreach ($rules as $index => $rule) {
            $turf->rules()->create([
                'title' => $rule['title'],
                'description' => $rule['description'],
                'sort_order' => $index + 1,
                'is_active' => $rule['is_active'],
            ]);
        }
    }
}
