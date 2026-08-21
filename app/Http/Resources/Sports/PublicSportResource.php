<?php

namespace App\Http\Resources\Sports;

use App\Http\Resources\ApiResource;
use App\Models\Sport;
use Illuminate\Http\Request;

/**
 * @mixin Sport
 */
final class PublicSportResource extends ApiResource
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
            'icon_asset_key' => $this->icon_asset_key,
            'icon_alt_text' => $this->icon_alt_text,
            'image_asset_key' => $this->image_asset_key,
            'image_alt_text' => $this->image_alt_text,
        ];
    }
}
