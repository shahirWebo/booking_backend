<?php

namespace App\Domain\Turfs\Actions;

use App\Domain\Turfs\Enums\TurfStatus;
use App\Models\Turf;

final class UpdateVendorTurfStatusAction
{
    public function execute(Turf $turf, TurfStatus $status): void
    {
        if ($turf->status === $status) {
            return;
        }

        $turf->forceFill([
            'status' => $status,
        ])->save();
    }
}
