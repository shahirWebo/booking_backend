<?php

namespace App\Domain\Sports\Actions;

use App\Domain\Sports\Repositories\SportRepository;
use App\Models\Sport;

final class CreateSportAction
{
    public function __construct(
        private readonly SportRepository $sports,
    ) {}

    /**
     * @param  array{name: string, code: string, description?: string|null}  $attributes
     */
    public function execute(array $attributes): Sport
    {
        return $this->sports->create($attributes);
    }
}
