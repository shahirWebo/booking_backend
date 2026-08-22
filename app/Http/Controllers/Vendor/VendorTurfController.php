<?php

namespace App\Http\Controllers\Vendor;

use App\Domain\Amenities\Repositories\AmenityRepository;
use App\Domain\Availability\Actions\ManageTurfAvailabilityAction;
use App\Domain\Availability\Actions\SyncTurfAvailabilityScheduleAction;
use App\Domain\Availability\Services\AvailabilityService;
use App\Domain\Sports\Repositories\SportRepository;
use App\Domain\Turfs\Actions\StoreVendorTurfAction;
use App\Domain\Turfs\Actions\UpdateVendorTurfAction;
use App\Domain\Turfs\Actions\UpdateVendorTurfStatusAction;
use App\Domain\Turfs\Enums\TurfStatus;
use App\Domain\Turfs\Repositories\TurfRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\CopyTurfAvailabilityScheduleRequest;
use App\Http\Requests\Vendor\ShowTurfAvailabilityRequest;
use App\Http\Requests\Vendor\StoreTurfMaintenanceBlockRequest;
use App\Http\Requests\Vendor\StoreTurfSlotBlockRequest;
use App\Http\Requests\Vendor\StoreVendorTurfRequest;
use App\Http\Requests\Vendor\UpdateTurfAvailabilityConfigurationRequest;
use App\Http\Requests\Vendor\UpdateTurfAvailabilityScheduleRequest;
use App\Http\Requests\Vendor\UpdateVendorTurfRequest;
use App\Http\Requests\Vendor\UpdateVendorTurfStatusRequest;
use App\Models\File;
use App\Models\Location;
use App\Models\MaintenanceBlock;
use App\Models\SlotBlock;
use App\Models\Turf;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
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
        private readonly SyncTurfAvailabilityScheduleAction $syncTurfAvailabilitySchedule,
        private readonly AvailabilityService $availability,
        private readonly ManageTurfAvailabilityAction $manageAvailability,
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
                $turf->loadMissing(['sports', 'amenities', 'images.file', 'rules', 'availabilityRules.timeRanges'])
            ),
            'sports' => $this->sportOptions(),
            'amenities' => $this->amenityOptions(),
            'available_images' => $this->availableImageOptions($turf->location, $turf),
            'routes' => [
                'index' => route('vendor.locations.turfs.index', $turf->location),
                'submit' => route('vendor.turfs.update', $turf),
                'update_status' => route('vendor.turfs.status.update', $turf),
                'update_availability_schedule' => route('vendor.turfs.availability-schedule.update', $turf),
                'availability' => route('vendor.turfs.availability', $turf),
                'pricing' => route('vendor.turfs.pricing', $turf),
                'location_edit' => route('vendor.locations.edit', $turf->location),
            ],
        ]);
    }

    public function availability(Turf $turf): InertiaResponse
    {
        Gate::authorize('update', $turf);
        $turf->loadMissing(['location', 'availabilityRules.timeRanges', 'slotBlocks', 'maintenanceBlocks']);

        return Inertia::render('vendor/turfs/Availability', [
            'turf' => [
                'id' => $turf->id,
                'name' => $turf->name,
                'location_name' => $turf->location->name,
                'timezone' => $turf->location->timezone,
                'booking_lead_time_minutes' => $turf->booking_lead_time_minutes,
                'advance_booking_window_days' => $turf->advance_booking_window_days,
                'default_slot_duration_minutes' => $turf->default_slot_duration_minutes,
                'availability_schedule' => $this->serializeAvailabilitySchedule($turf),
                'slot_blocks' => $turf->slotBlocks->map(fn (SlotBlock $block): array => [
                    'id' => $block->id,
                    'block_date' => $block->block_date,
                    'is_full_day' => $block->is_full_day,
                    'starts_at_time' => $block->starts_at_time,
                    'ends_at_time' => $block->ends_at_time,
                    'reason' => $block->reason,
                    'delete_url' => route('vendor.turfs.slot-blocks.destroy', [$turf, $block]),
                ])->values()->all(),
                'maintenance_blocks' => $turf->maintenanceBlocks->map(fn (MaintenanceBlock $block): array => [
                    'id' => $block->id,
                    'starts_at_local' => $block->starts_at->setTimezone($turf->location->timezone)->format('Y-m-d\\TH:i'),
                    'ends_at_local' => $block->ends_at->setTimezone($turf->location->timezone)->format('Y-m-d\\TH:i'),
                    'reason' => $block->reason,
                    'delete_url' => route('vendor.turfs.maintenance-blocks.destroy', [$turf, $block]),
                ])->values()->all(),
            ],
            'copy_targets' => Turf::query()
                ->whereKeyNot($turf->id)
                ->whereHas('location', fn ($query) => $query->where('vendor_id', $turf->location->vendor_id))
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Turf $target): array => ['id' => $target->id, 'name' => $target->name])
                ->all(),
            'routes' => [
                'back' => route('vendor.turfs.edit', $turf),
                'pricing' => route('vendor.turfs.pricing', $turf),
                'schedule' => route('vendor.turfs.availability-schedule.update', $turf),
                'configuration' => route('vendor.turfs.availability-configuration.update', $turf),
                'slots' => route('vendor.turfs.available-slots', $turf),
                'slot_blocks' => route('vendor.turfs.slot-blocks.store', $turf),
                'maintenance_blocks' => route('vendor.turfs.maintenance-blocks.store', $turf),
                'copy_schedule' => route('vendor.turfs.availability-schedule.copy', $turf),
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

    public function updateAvailabilitySchedule(UpdateTurfAvailabilityScheduleRequest $request, Turf $turf): RedirectResponse
    {
        $this->syncTurfAvailabilitySchedule->execute($turf, $request->availabilitySchedule());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Availability schedule updated successfully.'),
        ]);

        return to_route('vendor.turfs.availability', $turf);
    }

    public function availableSlots(ShowTurfAvailabilityRequest $request, Turf $turf): JsonResponse
    {
        $turf->loadMissing('location');

        return response()->json([
            'date' => $request->availabilityDate(),
            'location_timezone' => $turf->location->timezone,
            'slots' => array_map(
                fn ($slot): array => $slot->toArray(),
                $this->availability->slotsForDate($turf, $request->availabilityDate(), CarbonImmutable::now('UTC')),
            ),
        ]);
    }

    public function updateAvailabilityConfiguration(UpdateTurfAvailabilityConfigurationRequest $request, Turf $turf): RedirectResponse
    {
        $this->manageAvailability->updateConfiguration($turf, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Availability settings updated successfully.')]);

        return to_route('vendor.turfs.availability', $turf);
    }

    public function storeSlotBlock(StoreTurfSlotBlockRequest $request, Turf $turf): RedirectResponse
    {
        $this->manageAvailability->createSlotBlock($turf, $request->blockAttributes());

        return to_route('vendor.turfs.availability', $turf);
    }

    public function destroySlotBlock(Turf $turf, SlotBlock $slotBlock): RedirectResponse
    {
        Gate::authorize('update', $turf);
        $this->manageAvailability->deleteSlotBlock($turf, $slotBlock);

        return to_route('vendor.turfs.availability', $turf);
    }

    public function storeMaintenanceBlock(StoreTurfMaintenanceBlockRequest $request, Turf $turf): RedirectResponse
    {
        $turf->loadMissing('location');
        $this->manageAvailability->createMaintenanceBlock($turf, $request->startsAt($turf), $request->endsAt($turf), $request->reason());

        return to_route('vendor.turfs.availability', $turf);
    }

    public function destroyMaintenanceBlock(Turf $turf, MaintenanceBlock $maintenanceBlock): RedirectResponse
    {
        Gate::authorize('update', $turf);
        $this->manageAvailability->deleteMaintenanceBlock($turf, $maintenanceBlock);

        return to_route('vendor.turfs.availability', $turf);
    }

    public function copyAvailabilitySchedule(CopyTurfAvailabilityScheduleRequest $request, Turf $turf): RedirectResponse
    {
        $this->manageAvailability->copySchedule($turf, $request->target());

        return to_route('vendor.turfs.availability', $turf);
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
            'availability_schedule' => $this->serializeAvailabilitySchedule($turf),
            'routes' => [
                'edit' => route('vendor.turfs.edit', $turf),
            ],
        ];
    }

    /**
     * @return list<array{weekday: int, is_active: bool, time_ranges: list<array{starts_at_time: string, ends_at_time: string, ends_next_day: bool}>}>
     */
    private function serializeAvailabilitySchedule(Turf $turf): array
    {
        if (! $turf->relationLoaded('availabilityRules')) {
            return [];
        }

        $schedule = [];

        foreach ($turf->availabilityRules as $rule) {
            $ranges = [];

            foreach ($rule->timeRanges as $range) {
                $ranges[] = [
                    'starts_at_time' => $range->starts_at_time,
                    'ends_at_time' => $range->ends_at_time,
                    'ends_next_day' => $range->ends_next_day,
                ];
            }

            $schedule[] = [
                'weekday' => $rule->weekday,
                'is_active' => $rule->is_active,
                'time_ranges' => $ranges,
            ];
        }

        return $schedule;
    }
}
