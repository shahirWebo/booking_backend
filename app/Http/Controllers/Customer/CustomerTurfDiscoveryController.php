<?php

namespace App\Http\Controllers\Customer;

use App\Domain\Amenities\Repositories\AmenityRepository;
use App\Domain\Locations\Repositories\LocationRepository;
use App\Domain\Search\Services\TurfDiscoveryService;
use App\Domain\Sports\Repositories\SportRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ShowCustomerTurfSearchRequest;
use App\Models\Turf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class CustomerTurfDiscoveryController extends Controller
{
    public function __construct(
        private readonly TurfDiscoveryService $discovery,
        private readonly SportRepository $sports,
        private readonly AmenityRepository $amenities,
        private readonly LocationRepository $locations,
    ) {}

    public function index(ShowCustomerTurfSearchRequest $request): InertiaResponse
    {
        $paginator = $this->discovery->search(
            $request->validated(),
            $request->url(),
            $request->query(),
        );

        return Inertia::render('customer/Search', [
            'filters' => [
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'city' => $request->input('city'),
                'locality' => $request->input('locality'),
                'turf_name' => $request->input('turf_name'),
                'sport_ids' => array_values(array_map('intval', $request->input('sport_ids', []))),
                'amenity_ids' => array_values(array_map('intval', $request->input('amenity_ids', []))),
                'min_price' => $request->input('min_price'),
                'max_price' => $request->input('max_price'),
                'distance_meters' => $request->input('distance_meters'),
                'date' => $request->input('date'),
                'is_indoor' => $request->input('is_indoor'),
                'sort' => $request->input('sort', $request->filled('latitude') ? 'distance' : 'recommended'),
                'per_page' => (int) $request->input('per_page', 12),
            ],
            'options' => [
                'sports' => $this->sports->allOrdered()
                    ->where('is_active', true)
                    ->values()
                    ->map(fn ($sport): array => [
                        'id' => $sport->id,
                        'name' => $sport->name,
                    ])
                    ->all(),
                'amenities' => $this->amenities->allOrdered()
                    ->where('is_active', true)
                    ->values()
                    ->map(fn ($amenity): array => [
                        'id' => $amenity->id,
                        'name' => $amenity->name,
                    ])
                    ->all(),
                'location_areas' => $this->locations->searchableAreas()
                    ->map(fn ($location): array => [
                        'city' => $location->city,
                        'locality' => $location->locality,
                    ])
                    ->all(),
                'sorts' => [
                    ['value' => 'recommended', 'label' => 'Recommended'],
                    ['value' => 'distance', 'label' => 'Distance'],
                    ['value' => 'lowest_price', 'label' => 'Lowest price'],
                    ['value' => 'rating', 'label' => 'Rating'],
                    ['value' => 'popularity', 'label' => 'Popularity'],
                ],
            ],
            'results' => [
                'data' => $paginator->items(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
                'links' => [
                    'prev' => $paginator->previousPageUrl(),
                    'next' => $paginator->nextPageUrl(),
                ],
            ],
            'routes' => [
                'index' => route('customer.search.index'),
            ],
            'sort_support' => [
                'rating' => false,
                'popularity' => false,
            ],
        ]);
    }

    public function show(Request $request, Turf $turf): InertiaResponse
    {
        $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        return Inertia::render('customer/TurfDetails', [
            'turf' => $this->discovery->detail($turf, $request->input('date')),
            'routes' => [
                'search' => route('customer.search.index'),
                'show' => route('customer.turfs.show', $turf),
            ],
        ]);
    }
}
