<?php

namespace App\Domain\SystemSettings\Actions;

use App\Domain\SystemSettings\Data\SystemSettingsData;
use App\Domain\SystemSettings\Services\SystemSettingsService;

final class UpdateSystemSettingsAction
{
    public function __construct(
        private readonly SystemSettingsService $settings,
    ) {}

    /**
     * @param  array{
     *     booking: array<string, mixed>,
     *     otp: array<string, mixed>,
     *     support: array<string, mixed>
     * }  $attributes
     */
    public function execute(array $attributes): SystemSettingsData
    {
        return $this->settings->update($attributes);
    }
}
