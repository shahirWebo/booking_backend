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
     * @param  array{name: string, code: string, description?: string|null}  $attributes
     */
    public function create(array $attributes): Sport
    {
        /** @var Sport $sport */
        $sport = Sport::query()->create($attributes);

        return $sport;
    }

    /**
     * @param  array{name: string, code: string, description?: string|null}  $attributes
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
