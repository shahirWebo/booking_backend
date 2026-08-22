<?php

namespace App\Http\Controllers\Vendor;

use App\Domain\Amenities\Repositories\AmenityRepository;
use App\Domain\Locations\Actions\ResolveManagedVendorAction;
use App\Domain\Locations\Actions\StoreVendorLocationAction;
use App\Domain\Locations\Actions\UpdateVendorLocationAction;
use App\Domain\Locations\Actions\UpdateVendorLocationStatusAction;
use App\Domain\Locations\Enums\LocationStatus;
use App\Domain\Locations\Repositories\LocationRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\StoreVendorLocationRequest;
use App\Http\Requests\Vendor\UpdateVendorLocationRequest;
use App\Http\Requests\Vendor\UpdateVendorLocationStatusRequest;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class VendorLocationController extends Controller
{
    public function __construct(
        private readonly AmenityRepository $amenities,
        private readonly LocationRepository $locations,
        private readonly ResolveManagedVendorAction $resolveManagedVendor,
        private readonly StoreVendorLocationAction $storeVendorLocation,
        private readonly UpdateVendorLocationAction $updateVendorLocation,
        private readonly UpdateVendorLocationStatusAction $updateVendorLocationStatus,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $vendor = $this->resolveManagedVendor->execute($request->user());
        $locations = $this->locations->listForVendor($vendor);

        return Inertia::render('vendor/locations/Index', [
            'vendor' => [
                'id' => $vendor->id,
                'display_name' => $vendor->display_name,
                'legal_name' => $vendor->legal_name,
            ],
            'locations' => $locations->map(fn (Location $location): array => $this->serializeLocation($location))->all(),
            'routes' => [
                'create' => route('vendor.locations.create'),
                'index' => route('vendor.locations.index'),
            ],
        ]);
    }

    public function create(Request $request): InertiaResponse
    {
        $vendor = $this->resolveManagedVendor->execute($request->user());

        return Inertia::render('vendor/locations/Form', [
            'mode' => 'create',
            'vendor' => [
                'id' => $vendor->id,
                'display_name' => $vendor->display_name,
                'legal_name' => $vendor->legal_name,
            ],
            'location' => null,
            'amenities' => $this->amenityOptions(),
            'routes' => [
                'index' => route('vendor.locations.index'),
                'submit' => route('vendor.locations.store'),
            ],
        ]);
    }

    public function store(StoreVendorLocationRequest $request): RedirectResponse
    {
        $vendor = $this->resolveManagedVendor->execute($request->user());

        $this->storeVendorLocation->execute(
            $vendor,
            $request->locationAttributes(),
            $request->operatingHours(),
            $request->amenityIds(),
            $request->locationImages($vendor),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Location created successfully.'),
        ]);

        return to_route('vendor.locations.index');
    }

    public function edit(Location $location): InertiaResponse
    {
        Gate::authorize('view', $location);

        return Inertia::render('vendor/locations/Form', [
            'mode' => 'edit',
            'vendor' => [
                'id' => $location->vendor->id,
                'display_name' => $location->vendor->display_name,
                'legal_name' => $location->vendor->legal_name,
            ],
            'location' => $this->serializeLocation(
                $location->loadMissing(['vendor', 'operatingHours', 'amenities', 'images.file'])
            ),
            'amenities' => $this->amenityOptions(),
            'routes' => [
                'index' => route('vendor.locations.index'),
                'submit' => route('vendor.locations.update', $location),
                'update_status' => route('vendor.locations.status.update', $location),
            ],
        ]);
    }

    public function update(UpdateVendorLocationRequest $request, Location $location): RedirectResponse
    {
        $this->updateVendorLocation->execute(
            $location,
            $request->locationAttributes(),
            $request->operatingHours(),
            $request->amenityIds(),
            $request->locationImages($location->vendor, $location),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Location updated successfully.'),
        ]);

        return to_route('vendor.locations.edit', $location);
    }

    public function updateStatus(UpdateVendorLocationStatusRequest $request, Location $location): RedirectResponse
    {
        $this->updateVendorLocationStatus->execute($location, $request->status());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $request->status() === LocationStatus::Active
                ? __('Location activated successfully.')
                : __('Location deactivated successfully.'),
        ]);

        return to_route('vendor.locations.edit', $location);
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
     * @return array<string, mixed>
     */
    private function serializeLocation(Location $location): array
    {
        return [
            'id' => $location->id,
            'vendor_id' => $location->vendor_id,
            'name' => $location->name,
            'address_line_1' => $location->address_line_1,
            'address_line_2' => $location->address_line_2,
            'landmark' => $location->landmark,
            'locality' => $location->locality,
            'city' => $location->city,
            'state' => $location->state,
            'postal_code' => $location->postal_code,
            'country_code' => $location->country_code,
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
            'timezone' => $location->timezone,
            'status' => $location->status->value,
            'amenity_ids' => $location->amenities->pluck('id')->values()->all(),
            'operating_hours' => $location->operatingHours->map(fn ($hour): array => [
                'weekday' => $hour->weekday,
                'sequence' => $hour->sequence,
                'opens_at_time' => substr($hour->opens_at_time, 0, 5),
                'closes_at_time' => substr($hour->closes_at_time, 0, 5),
                'ends_next_day' => $hour->ends_next_day,
            ])->values()->all(),
            'images' => $location->images->map(fn ($image): array => [
                'id' => $image->id,
                'file_id' => $image->file_id,
                'sort_order' => $image->sort_order,
                'caption' => $image->caption,
                'alt_text' => $image->alt_text,
                'file' => [
                    'id' => $image->file?->id,
                    'status' => $image->file?->status?->value,
                    'original_name' => $image->file?->original_name,
                ],
            ])->values()->all(),
        ];
    }
}
