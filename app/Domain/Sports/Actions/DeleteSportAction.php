<?php

namespace App\Domain\Sports\Actions;

use App\Domain\Sports\Repositories\SportRepository;
use App\Models\Sport;

final class DeleteSportAction
{
    public function __construct(
        private readonly SportRepository $sports,
    ) {}

    public function execute(Sport $sport): void
    {
        $this->sports->delete($sport);
    }
}
