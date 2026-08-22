<?php

namespace App\Http\Controllers\Vendor;

use App\Domain\Amenities\Repositories\AmenityRepository;
use App\Domain\Sports\Repositories\SportRepository;
use App\Domain\Turfs\Actions\StoreVendorTurfAction;
use App\Domain\Turfs\Actions\UpdateVendorTurfAction;
use App\Domain\Turfs\Actions\UpdateVendorTurfStatusAction;
use App\Domain\Turfs\Enums\TurfStatus;
use App\Domain\Turfs\Repositories\TurfRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\StoreVendorTurfRequest;
use App\Http\Requests\Vendor\UpdateVendorTurfRequest;
use App\Http\Requests\Vendor\UpdateVendorTurfStatusRequest;
use App\Models\File;
use App\Models\Location;
use App\Models\Turf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class VendorTurfController extends Controller
{
    public function __construct(
        private readonly AmenityRepository $amenities,
        private readonly SportRepository $sports,
        private readonly TurfRepository $turfs,
        private readonly StoreVendorTurfAction $storeVendorTurf,
        private readonly UpdateVendorTurfAction $updateVendorTurf,
        private readonly UpdateVendorTurfStatusAction $updateVendorTurfStatus,
    ) {}

    public function index(Location $location): InertiaResponse
    {
        Gate::authorize('update', $location);

        return Inertia::render('vendor/turfs/Index', [
            'vendor' => [
                'id' => $location->vendor->id,
                'display_name' => $location->vendor->display_name,
                'legal_name' => $location->vendor->legal_name,
            ],
            'location' => [
                'id' => $location->id,
                'name' => $location->name,
                'city' => $location->city,
                'state' => $location->state,
                'status' => $location->status->value,
            ],
            'turfs' => $this->turfs->listForLocation($location)
                ->map(fn (Turf $turf): array => $this->serializeTurf($turf))
                ->all(),
            'routes' => [
                'create' => route('vendor.locations.turfs.create', $location),
                'location_edit' => route('vendor.locations.edit', $location),
                'locations_index' => route('vendor.locations.index'),
            ],
        ]);
    }

    public function create(Location $location): InertiaResponse
    {
        Gate::authorize('update', $location);

        return Inertia::render('vendor/turfs/Form', [
            'mode' => 'create',
            'vendor' => [
                'id' => $location->vendor->id,
                'display_name' => $location->vendor->display_name,
                'legal_name' => $location->vendor->legal_name,
            ],
            'location' => [
                'id' => $location->id,
                'name' => $location->name,
                'city' => $location->city,
                'state' => $location->state,
                'status' => $location->status->value,
            ],
            'turf' => null,
            'sports' => $this->sportOptions(),
            'amenities' => $this->amenityOptions(),
            'available_images' => $this->availableImageOptions($location),
            'routes' => [
                'index' => route('vendor.locations.turfs.index', $location),
                'submit' => route('vendor.locations.turfs.store', $location),
                'location_edit' => route('vendor.locations.edit', $location),
            ],
        ]);
    }

    public function store(StoreVendorTurfRequest $request, Location $location): RedirectResponse
    {
        Gate::authorize('update', $location);

        $this->storeVendorTurf->execute(
            $location,
            $request->turfAttributes(),
            $request->sportIds(),
            $request->amenityIds(),
            $request->turfImages($location->vendor),
            $request->rulesPayload(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Turf created successfully.'),
        ]);

        return to_route('vendor.locations.turfs.index', $location);
    }

    public function edit(Turf $turf): InertiaResponse
    {
        Gate::authorize('update', $turf);

        $turf->loadMissing(['location.vendor']);

        return Inertia::render('vendor/turfs/Form', [
            'mode' => 'edit',
            'vendor' => [
                'id' => $turf->location->vendor->id,
                'display_name' => $turf->location->vendor->display_name,
                'legal_name' => $turf->location->vendor->legal_name,
            ],
            'location' => [
                'id' => $turf->location->id,
                'name' => $turf->location->name,
                'city' => $turf->location->city,
                'state' => $turf->location->state,
                'status' => $turf->location->status->value,
            ],
            'turf' => $this->serializeTurf(
                $turf->loadMissing(['sports', 'amenities', 'images.file', 'rules'])
            ),
            'sports' => $this->sportOptions(),
            'amenities' => $this->amenityOptions(),
            'available_images' => $this->availableImageOptions($turf->location, $turf),
            'routes' => [
                'index' => route('vendor.locations.turfs.index', $turf->location),
                'submit' => route('vendor.turfs.update', $turf),
                'update_status' => route('vendor.turfs.status.update', $turf),
                'location_edit' => route('vendor.locations.edit', $turf->location),
            ],
        ]);
    }

    public function update(UpdateVendorTurfRequest $request, Turf $turf): RedirectResponse
    {
        $this->updateVendorTurf->execute(
            $turf,
            $request->turfAttributes(),
            $request->sportIds(),
            $request->amenityIds(),
            $request->turfImages($turf->location->vendor, $turf),
            $request->rulesPayload(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Turf updated successfully.'),
        ]);

        return to_route('vendor.turfs.edit', $turf);
    }

    public function updateStatus(UpdateVendorTurfStatusRequest $request, Turf $turf): RedirectResponse
    {
        $this->updateVendorTurfStatus->execute($turf, $request->status());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $request->status() === TurfStatus::Active
                ? __('Turf activated successfully.')
                : __('Turf deactivated successfully.'),
        ]);

        return to_route('vendor.turfs.edit', $turf);
    }

    /**
     * @return list<array{id: int, name: string, code: string}>
     */
    private function sportOptions(): array
    {
        return array_values($this->sports->allActiveOrdered()
            ->map(fn ($sport): array => [
                'id' => $sport->id,
                'name' => $sport->name,
                'code' => $sport->code,
            ])
            ->values()
            ->all());
    }

    /**
     * @return list<array{id: int, name: string, code: string}>
     */
    private function amenityOptions(): array
    {
        return array_values($this->amenities->allOrdered()
            ->where('is_active', true)
            ->map(fn ($amenity): array => [
                'id' => $amenity->id,
                'name' => $amenity->name,
                'code' => $amenity->code,
            ])
            ->values()
            ->all());
    }

    /**
     * @return list<array{id: int, original_name: string|null, canonical_extension: string|null, size_bytes: int|null, status: string|null, attached_to_current_turf: bool}>
     */
    private function availableImageOptions(Location $location, ?Turf $turf = null): array
    {
        return array_values($this->turfs->availableImageFiles($location->vendor, $turf)
            ->map(function (File $file): array {
                return [
                    'id' => $file->id,
                    'original_name' => $file->original_name,
                    'canonical_extension' => $file->canonical_extension,
                    'size_bytes' => $file->size_bytes,
                    'status' => $file->status->value,
                    'attached_to_current_turf' => $file->turfImages->isNotEmpty(),
                ];
            })
            ->all());
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTurf(Turf $turf): array
    {
        return [
            'id' => $turf->id,
            'location_id' => $turf->location_id,
            'name' => $turf->name,
            'description' => $turf->description,
            'status' => $turf->status->value,
            'surface_type' => $turf->surface_type,
            'is_indoor' => $turf->is_indoor,
            'capacity_count' => $turf->capacity_count,
            'length_meters' => $turf->length_meters,
            'width_meters' => $turf->width_meters,
            'sport_ids' => $turf->sports->pluck('id')->values()->all(),
            'amenity_ids' => $turf->amenities->pluck('id')->values()->all(),
            'images' => $turf->images->map(fn ($image): array => [
                'id' => $image->id,
                'file_id' => $image->file_id,
                'sort_order' => $image->sort_order,
                'caption' => $image->caption,
                'alt_text' => $image->alt_text,
            ])->values()->all(),
            'rules' => $turf->rules->map(fn ($rule): array => [
                'id' => $rule->id,
                'title' => $rule->title,
                'description' => $rule->description,
                'sort_order' => $rule->sort_order,
                'is_active' => $rule->is_active,
            ])->values()->all(),
            'routes' => [
                'edit' => route('vendor.turfs.edit', $turf),
            ],
        ];
    }
}
