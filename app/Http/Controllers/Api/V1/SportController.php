<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Sports\Repositories\SportRepository;
use App\Http\Controllers\Controller;
use App\Http\Resources\Sports\PublicSportResource;
use App\Models\Sport;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

final class SportController extends Controller
{
    public function __construct(
        private readonly SportRepository $sports,
    ) {}

    public function index(): JsonResponse
    {
        return ApiResponse::collection(
            $this->sports->allActiveOrdered()->map(
                static fn (Sport $sport): PublicSportResource => new PublicSportResource($sport),
            )->all(),
        );
    }
}
