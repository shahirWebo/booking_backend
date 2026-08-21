<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Amenities\Actions\CreateAmenityAction;
use App\Domain\Amenities\Actions\DeleteAmenityAction;
use App\Domain\Amenities\Actions\UpdateAmenityAction;
use App\Domain\Amenities\Repositories\AmenityRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreAmenityRequest;
use App\Http\Requests\Api\V1\Admin\UpdateAmenityRequest;
use App\Http\Resources\Admin\AmenityResource;
use App\Models\Amenity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

final class AmenityManagementController extends Controller
{
    public function __construct(
        private readonly AmenityRepository $amenities,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('admin/operations/AmenitiesIndex', [
            'amenities' => $this->serializeAmenities($request),
            'routes' => [
                'create' => route('admin.amenities.create'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/operations/AmenityForm', [
            'amenity' => null,
            'mode' => 'create',
            'routes' => [
                'index' => route('admin.amenities.index'),
                'submit' => route('admin.amenities.store'),
            ],
        ]);
    }

    public function edit(Request $request, Amenity $amenity): Response
    {
        return Inertia::render('admin/operations/AmenityForm', [
            'amenity' => [
                ...(new AmenityResource($amenity))->resolve($request),
            ],
            'mode' => 'edit',
            'routes' => [
                'index' => route('admin.amenities.index'),
                'submit' => route('admin.amenities.update', $amenity),
            ],
        ]);
    }

    public function store(
        StoreAmenityRequest $request,
        CreateAmenityAction $createAmenity,
    ): RedirectResponse {
        $createAmenity->execute($request->amenityAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Amenity created.')]);

        return to_route('admin.amenities.index');
    }

    public function update(
        UpdateAmenityRequest $request,
        Amenity $amenity,
        UpdateAmenityAction $updateAmenity,
    ): RedirectResponse {
        $updateAmenity->execute($amenity, $request->amenityAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Amenity updated.')]);

        return to_route('admin.amenities.index');
    }

    public function destroy(
        Amenity $amenity,
        DeleteAmenityAction $deleteAmenity,
    ): RedirectResponse {
        $deleteAmenity->execute($amenity);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Amenity deleted.')]);

        return to_route('admin.amenities.index');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function serializeAmenities(Request $request): array
    {
        /** @var Collection<int, Amenity> $amenities */
        $amenities = $this->amenities->allOrdered();

        return $amenities
            ->map(fn (Amenity $amenity): array => [
                ...(new AmenityResource($amenity))->resolve($request),
                'routes' => [
                    'edit' => route('admin.amenities.edit', $amenity),
                    'update' => route('admin.amenities.update', $amenity),
                    'destroy' => route('admin.amenities.destroy', $amenity),
                ],
            ])
            ->values()
            ->all();
    }
}
