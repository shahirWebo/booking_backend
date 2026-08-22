<?php

namespace App\Domain\Locations\Actions;

use App\Domain\Locations\Enums\LocationStatus;
use App\Models\Location;

final class UpdateVendorLocationStatusAction
{
    public function execute(Location $location, LocationStatus $status): void
    {
        if ($location->status === $status) {
            return;
        }

        $location->forceFill([
            'status' => $status,
        ])->save();
    }
}
