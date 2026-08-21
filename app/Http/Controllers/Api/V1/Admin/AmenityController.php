<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Amenities\Actions\CreateAmenityAction;
use App\Domain\Amenities\Actions\DeleteAmenityAction;
use App\Domain\Amenities\Actions\UpdateAmenityAction;
use App\Domain\Amenities\Repositories\AmenityRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreAmenityRequest;
use App\Http\Requests\Api\V1\Admin\UpdateAmenityRequest;
use App\Http\Resources\Admin\AmenityResource;
use App\Models\Amenity;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class AmenityController extends Controller
{
    public function __construct(
        private readonly AmenityRepository $amenities,
    ) {}

    public function index(): JsonResponse
    {
        return ApiResponse::collection(
            $this->amenities->allOrdered()->map(
                static fn (Amenity $amenity): AmenityResource => new AmenityResource($amenity),
            )->all(),
        );
    }

    public function store(
        StoreAmenityRequest $request,
        CreateAmenityAction $createAmenity,
    ): JsonResponse {
        $amenity = $createAmenity->execute($request->amenityAttributes());

        return ApiResponse::created(
            new AmenityResource($amenity),
            route('api.v1.admin.amenities.show', $amenity),
            'Amenity created.',
        );
    }

    public function show(Amenity $amenity): JsonResponse
    {
        return ApiResponse::success(new AmenityResource($amenity));
    }

    public function update(
        UpdateAmenityRequest $request,
        Amenity $amenity,
        UpdateAmenityAction $updateAmenity,
    ): JsonResponse {
        $amenity = $updateAmenity->execute($amenity, $request->amenityAttributes());

        return ApiResponse::success(
            new AmenityResource($amenity),
            message: 'Amenity updated.',
        );
    }

    public function destroy(
        Amenity $amenity,
        DeleteAmenityAction $deleteAmenity,
    ): Response {
        $deleteAmenity->execute($amenity);

        return ApiResponse::noContent();
    }
}
