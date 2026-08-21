<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\ApiResource;
use App\Models\Amenity;
use Illuminate\Http\Request;

/**
 * @mixin Amenity
 */
final class AmenityResource extends ApiResource
{
    /**
     * @return array<string, int|string|null>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
