<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Sports\Actions\CreateSportAction;
use App\Domain\Sports\Actions\DeleteSportAction;
use App\Domain\Sports\Actions\UpdateSportAction;
use App\Domain\Sports\Repositories\SportRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreSportRequest;
use App\Http\Requests\Api\V1\Admin\UpdateSportRequest;
use App\Http\Resources\Admin\SportResource;
use App\Models\Sport;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class SportController extends Controller
{
    public function __construct(
        private readonly SportRepository $sports,
    ) {}

    public function index(): JsonResponse
    {
        return ApiResponse::collection(
            $this->sports->allOrdered()->map(
                static fn (Sport $sport): SportResource => new SportResource($sport),
            )->all(),
        );
    }

    public function store(
        StoreSportRequest $request,
        CreateSportAction $createSport,
    ): JsonResponse {
        $sport = $createSport->execute($request->sportAttributes());

        return ApiResponse::created(
            new SportResource($sport),
            route('api.v1.admin.sports.show', $sport),
            'Sport created.',
        );
    }

    public function show(Sport $sport): JsonResponse
    {
        return ApiResponse::success(new SportResource($sport));
    }

    public function update(
        UpdateSportRequest $request,
        Sport $sport,
        UpdateSportAction $updateSport,
    ): JsonResponse {
        $sport = $updateSport->execute($sport, $request->sportAttributes());

        return ApiResponse::success(
            new SportResource($sport),
            message: 'Sport updated.',
        );
    }

    public function destroy(
        Sport $sport,
        DeleteSportAction $deleteSport,
    ): Response {
        $deleteSport->execute($sport);

        return ApiResponse::noContent();
    }
}
