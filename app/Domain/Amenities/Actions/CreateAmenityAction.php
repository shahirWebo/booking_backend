<?php

namespace App\Domain\Amenities\Actions;

use App\Domain\Amenities\Repositories\AmenityRepository;
use App\Models\Amenity;

final class CreateAmenityAction
{
    public function __construct(
        private readonly AmenityRepository $amenities,
    ) {}

    /**
     * @param  array{name: string, code: string, description?: string|null, is_active?: bool}  $attributes
     */
    public function execute(array $attributes): Amenity
    {
        return $this->amenities->create($attributes);
    }
}
