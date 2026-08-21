<?php

namespace App\Domain\Sports\Actions;

use App\Domain\Sports\Repositories\SportRepository;
use App\Models\Sport;

final class UpdateSportAction
{
    public function __construct(
        private readonly SportRepository $sports,
    ) {}

    /**
     * @param  array{name: string, code: string, description?: string|null}  $attributes
     */
    public function execute(Sport $sport, array $attributes): Sport
    {
        return $this->sports->update($sport, $attributes);
    }
}
