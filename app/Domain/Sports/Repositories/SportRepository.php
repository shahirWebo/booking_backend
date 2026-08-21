<?php

namespace App\Domain\Sports\Repositories;

use App\Models\Sport;
use Illuminate\Database\Eloquent\Collection;

final class SportRepository
{
    /**
     * @return Collection<int, Sport>
     */
    public function allOrdered(): Collection
    {
        return Sport::query()
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Sport>
     */
    public function allActiveOrdered(): Collection
    {
        return Sport::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array{name: string, code: string, description?: string|null, is_active?: bool, icon_asset_key?: string|null, icon_alt_text?: string|null, image_asset_key?: string|null, image_alt_text?: string|null}  $attributes
     */
    public function create(array $attributes): Sport
    {
        /** @var Sport $sport */
        $sport = Sport::query()->create($attributes);

        return $sport->refresh();
    }

    /**
     * @param  array{name: string, code: string, description?: string|null, is_active?: bool, icon_asset_key?: string|null, icon_alt_text?: string|null, image_asset_key?: string|null, image_alt_text?: string|null}  $attributes
     */
    public function update(Sport $sport, array $attributes): Sport
    {
        $sport->fill($attributes);
        $sport->save();

        return $sport->refresh();
    }

    public function delete(Sport $sport): void
    {
        $sport->delete();
    }
}
