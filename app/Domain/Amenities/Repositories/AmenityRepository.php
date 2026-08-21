<?php

namespace App\Domain\Amenities\Repositories;

use App\Models\Amenity;
use Illuminate\Database\Eloquent\Collection;

final class AmenityRepository
{
    /**
     * @return Collection<int, Amenity>
     */
    public function allOrdered(): Collection
    {
        return Amenity::query()
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array{name: string, code: string, description?: string|null}  $attributes
     */
    public function create(array $attributes): Amenity
    {
        /** @var Amenity $amenity */
        $amenity = Amenity::query()->create($attributes);

        return $amenity->refresh();
    }

    /**
     * @param  array{name: string, code: string, description?: string|null}  $attributes
     */
    public function update(Amenity $amenity, array $attributes): Amenity
    {
        $amenity->fill($attributes);
        $amenity->save();

        return $amenity->refresh();
    }

    public function delete(Amenity $amenity): void
    {
        $amenity->delete();
    }
}
