<?php

namespace App\Domain\Turfs\Actions;

use App\Domain\Turfs\Repositories\TurfRepository;
use App\Models\File;
use App\Models\Location;
use App\Models\Turf;
use Illuminate\Support\Facades\DB;

final class StoreVendorTurfAction
{
    public function __construct(
        private readonly TurfRepository $turfs,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $sportIds
     * @param  list<int>  $amenityIds
     * @param  list<array{file: File, caption: string|null, alt_text: string|null}>  $images
     * @param  list<array{title: string, description: string, is_active: bool}>  $rules
     */
    public function execute(
        Location $location,
        array $attributes,
        array $sportIds,
        array $amenityIds,
        array $images,
        array $rules,
    ): Turf {
        /** @var Turf $turf */
        $turf = DB::transaction(function () use ($location, $attributes, $sportIds, $amenityIds, $images, $rules): Turf {
            $turf = $this->turfs->create($location, $attributes);
            $this->turfs->syncSports($turf, $sportIds);
            $this->turfs->syncAmenities($turf, $amenityIds);
            $this->turfs->syncImages($turf, $images);
            $this->turfs->syncRules($turf, $rules);

            return $turf;
        });

        return $turf->load(['location.vendor', 'sports', 'amenities', 'images.file', 'rules']);
    }
}
