<?php

namespace App\Domain\Amenities\Actions;

use App\Domain\Amenities\Repositories\AmenityRepository;
use App\Models\Amenity;

final class UpdateAmenityAction
{
    public function __construct(
        private readonly AmenityRepository $amenities,
    ) {}

    /**
     * @param  array{name: string, code: string, description?: string|null}  $attributes
     */
    public function execute(Amenity $amenity, array $attributes): Amenity
    {
        return $this->amenities->update($amenity, $attributes);
    }
}
