<?php

namespace App\Domain\Amenities\Actions;

use App\Domain\Amenities\Repositories\AmenityRepository;
use App\Models\Amenity;

final class DeleteAmenityAction
{
    public function __construct(
        private readonly AmenityRepository $amenities,
    ) {}

    public function execute(Amenity $amenity): void
    {
        $this->amenities->delete($amenity);
    }
}
