<?php

namespace App\Http\Resources\Admin;

use App\Domain\SystemSettings\Data\SystemSettingsData;
use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;

/**
 * @mixin SystemSettingsData
 */
final class SystemSettingsResource extends ApiResource
{
    /**
     * @return array{
     *     booking: array<string, int>,
     *     otp: array<string, int>,
     *     support: array<string, string>
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var SystemSettingsData $settings */
        $settings = $this->resource;

        return $settings->toArray();
    }
}
