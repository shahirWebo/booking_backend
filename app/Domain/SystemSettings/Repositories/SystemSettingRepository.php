<?php

namespace App\Domain\SystemSettings\Repositories;

use App\Models\SystemSetting;
use Illuminate\Database\Eloquent\Collection;

final class SystemSettingRepository
{
    /**
     * @return Collection<int, SystemSetting>
     */
    public function all(): Collection
    {
        return SystemSetting::query()
            ->orderBy('key')
            ->get();
    }

    /**
     * @param  array<string, array<string, mixed>>  $attributesByKey
     */
    public function upsertMany(array $attributesByKey): void
    {
        foreach ($attributesByKey as $key => $value) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }
    }
}
