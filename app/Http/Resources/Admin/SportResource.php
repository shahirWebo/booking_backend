<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\ApiResource;
use App\Models\Sport;
use Illuminate\Http\Request;

/**
 * @mixin Sport
 */
final class SportResource extends ApiResource
{
    /**
     * @return array<string, bool|int|string|null>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'icon_asset_key' => $this->icon_asset_key,
            'icon_alt_text' => $this->icon_alt_text,
            'image_asset_key' => $this->image_asset_key,
            'image_alt_text' => $this->image_alt_text,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
