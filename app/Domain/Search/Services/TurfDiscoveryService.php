<?php

namespace App\Domain\Search\Services;

use App\Domain\Availability\Services\AvailabilityService;
use App\Domain\Locations\Enums\LocationStatus;
use App\Domain\Turfs\Enums\TurfStatus;
use App\Models\Amenity;
use App\Models\PricingRule;
use App\Models\Sport;
use App\Models\Turf;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class TurfDiscoveryService
{
    private const DEFAULT_DISTANCE_METERS = 25000;

    private const EARTH_RADIUS_METERS = 6371008.8;

    public function __construct(
        private readonly AvailabilityService $availability,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $query
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function search(array $filters, string $path, array $query = []): LengthAwarePaginator
    {
        $searchContext = $this->searchContext($filters);

        $turfs = $this->baseQuery()
            ->when($searchContext['has_coordinates'], function (Builder $query) use ($searchContext): void {
                $this->applyBoundingBox($query, $searchContext['latitude'], $searchContext['longitude'], $searchContext['distance_meters']);
            })
            ->when($searchContext['city'] !== null || $searchContext['locality'] !== null, function (Builder $query) use ($searchContext): void {
                $query->whereHas('location', function (Builder $locationQuery) use ($searchContext): void {
                    if ($searchContext['city'] !== null) {
                        $locationQuery->where('city', 'like', '%'.$searchContext['city'].'%');
                    }

                    if ($searchContext['locality'] !== null) {
                        $locationQuery->where('locality', 'like', '%'.$searchContext['locality'].'%');
                    }
                });
            })
            ->when($searchContext['turf_name'] !== null, fn (Builder $query): Builder => $query->where('name', 'like', '%'.$searchContext['turf_name'].'%'))
            ->when($searchContext['sport_ids'] !== [], fn (Builder $query): Builder => $query->whereHas('sports', fn (Builder $sportQuery): Builder => $sportQuery->whereIn('sports.id', $searchContext['sport_ids'])))
            ->when($searchContext['is_indoor'] !== null, fn (Builder $query): Builder => $query->where('is_indoor', $searchContext['is_indoor']))
            ->when($searchContext['amenity_ids'] !== [], function (Builder $query) use ($searchContext): void {
                foreach ($searchContext['amenity_ids'] as $amenityId) {
                    $query->where(function (Builder $amenityQuery) use ($amenityId): void {
                        $amenityQuery
                            ->whereHas('amenities', fn (Builder $turfAmenities): Builder => $turfAmenities->where('amenities.id', $amenityId))
                            ->orWhereHas('location.amenities', fn (Builder $locationAmenities): Builder => $locationAmenities->where('amenities.id', $amenityId));
                    });
                }
            })
            ->get();

        $results = $turfs
            ->map(fn (Turf $turf): array => $this->serializeSearchResult($turf, $searchContext))
            ->filter(function (array $result) use ($searchContext): bool {
                if ($searchContext['has_coordinates']
                    && $result['distance_meters'] !== null
                    && $result['distance_meters'] > $searchContext['distance_meters']) {
                    return false;
                }

                if ($searchContext['min_price_minor'] !== null
                    && (($result['pricing_summary']['starting_price_minor'] ?? null) === null
                        || $result['pricing_summary']['starting_price_minor'] < $searchContext['min_price_minor'])) {
                    return false;
                }

                if ($searchContext['max_price_minor'] !== null
                    && (($result['pricing_summary']['starting_price_minor'] ?? null) === null
                        || $result['pricing_summary']['starting_price_minor'] > $searchContext['max_price_minor'])) {
                    return false;
                }

                if ($searchContext['date'] !== null
                    && ! data_get($result, 'availability_summary.has_availability', false)) {
                    return false;
                }

                return true;
            })
            ->values();

        $sorted = $this->sortResults($results, $searchContext['sort'], $searchContext['has_coordinates']);

        $page = max(1, $searchContext['page']);
        $perPage = (int) $searchContext['per_page'];
        $total = $sorted->count();
        $items = $sorted->forPage($page, $perPage)->values()->all();

        $paginator = new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => $path,
            'pageName' => 'page',
        ]);

        $paginator->appends($query);

        return $paginator;
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(Turf $turf, ?string $date = null): array
    {
        $activeTurf = $this->baseQuery()
            ->whereKey($turf->id)
            ->firstOrFail();

        $selectedDate = $date ?? CarbonImmutable::now($activeTurf->location->timezone)->format('Y-m-d');
        $availabilitySummary = $this->availabilitySummary($activeTurf, $selectedDate);

        return [
            'id' => $activeTurf->id,
            'name' => $activeTurf->name,
            'description' => $activeTurf->description,
            'surface_type' => $activeTurf->surface_type,
            'is_indoor' => $activeTurf->is_indoor,
            'capacity_count' => $activeTurf->capacity_count,
            'dimensions' => [
                'length_meters' => $activeTurf->length_meters,
                'width_meters' => $activeTurf->width_meters,
            ],
            'location' => [
                'id' => $activeTurf->location->id,
                'name' => $activeTurf->location->name,
                'address_line_1' => $activeTurf->location->address_line_1,
                'address_line_2' => $activeTurf->location->address_line_2,
                'landmark' => $activeTurf->location->landmark,
                'locality' => $activeTurf->location->locality,
                'city' => $activeTurf->location->city,
                'state' => $activeTurf->location->state,
                'postal_code' => $activeTurf->location->postal_code,
                'country_code' => $activeTurf->location->country_code,
                'latitude' => $activeTurf->location->latitude,
                'longitude' => $activeTurf->location->longitude,
                'timezone' => $activeTurf->location->timezone,
            ],
            'sports' => $activeTurf->sports
                ->where('is_active', true)
                ->sortBy('name')
                ->values()
                ->map(fn (Sport $sport): array => [
                    'id' => $sport->id,
                    'name' => $sport->name,
                    'code' => $sport->code,
                ])
                ->all(),
            'amenities' => $this->collectAmenities($activeTurf)->all(),
            'pricing_summary' => $this->pricingSummary($activeTurf),
            'availability_summary' => $availabilitySummary,
            'rules' => $activeTurf->rules
                ->where('is_active', true)
                ->sortBy('sort_order')
                ->values()
                ->map(fn ($rule): array => [
                    'id' => $rule->id,
                    'title' => $rule->title,
                    'description' => $rule->description,
                ])
                ->all(),
            'images' => $activeTurf->images->map(fn ($image): array => [
                'id' => $image->id,
                'caption' => $image->caption,
                'alt_text' => $image->alt_text,
                'original_name' => $image->file?->original_name,
            ])->all(),
        ];
    }

    /**
     * @return Builder<Turf>
     */
    private function baseQuery(): Builder
    {
        return Turf::query()
            ->where('status', TurfStatus::Active->value)
            ->whereHas(
                'location',
                fn (Builder $query): Builder => $query->where(
                    'status',
                    LocationStatus::Active->value,
                ),
            )
            ->with([
                'location.amenities',
                'sports',
                'amenities',
                'images.file',
                'rules',
                'pricingRules',
                'availabilityRules.timeRanges',
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     latitude: float|null,
     *     longitude: float|null,
     *     has_coordinates: bool,
     *     distance_meters: int,
     *     city: string|null,
     *     locality: string|null,
     *     turf_name: string|null,
     *     sport_ids: list<int>,
     *     amenity_ids: list<int>,
     *     is_indoor: bool|null,
     *     min_price_minor: int|null,
     *     max_price_minor: int|null,
     *     date: string|null,
     *     sort: string,
     *     page: int,
     *     per_page: int
     * }
     */
    private function searchContext(array $filters): array
    {
        $latitude = isset($filters['latitude']) ? (float) $filters['latitude'] : null;
        $longitude = isset($filters['longitude']) ? (float) $filters['longitude'] : null;
        $distanceMeters = isset($filters['distance_meters'])
            ? (int) $filters['distance_meters']
            : self::DEFAULT_DISTANCE_METERS;

        return [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'has_coordinates' => $latitude !== null && $longitude !== null,
            'distance_meters' => $distanceMeters,
            'city' => $this->normalizedString($filters['city'] ?? null),
            'locality' => $this->normalizedString($filters['locality'] ?? null),
            'turf_name' => $this->normalizedString($filters['turf_name'] ?? null),
            'sport_ids' => array_values(array_map('intval', $filters['sport_ids'] ?? [])),
            'amenity_ids' => array_values(array_map('intval', $filters['amenity_ids'] ?? [])),
            'is_indoor' => array_key_exists('is_indoor', $filters) && $filters['is_indoor'] !== null
                ? filter_var($filters['is_indoor'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE)
                : null,
            'min_price_minor' => $this->minorUnits($filters['min_price'] ?? null),
            'max_price_minor' => $this->minorUnits($filters['max_price'] ?? null),
            'date' => $filters['date'] ?? null,
            'sort' => $filters['sort'] ?? (($latitude !== null && $longitude !== null) ? 'distance' : 'recommended'),
            'page' => isset($filters['page']) ? (int) $filters['page'] : 1,
            'per_page' => isset($filters['per_page']) ? (int) $filters['per_page'] : 12,
        ];
    }

    private function normalizedString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function minorUnits(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) round(((float) $value) * 100);
    }

    /**
     * @param  Builder<Turf>  $query
     */
    private function applyBoundingBox(Builder $query, float $latitude, float $longitude, int $radiusMeters): void
    {
        $latitudeDelta = rad2deg($radiusMeters / self::EARTH_RADIUS_METERS);
        $cosine = cos(deg2rad($latitude));
        $longitudeDelta = abs($cosine) < 0.000001
            ? 180.0
            : rad2deg($radiusMeters / (self::EARTH_RADIUS_METERS * $cosine));

        $minLatitude = max(-90, $latitude - $latitudeDelta);
        $maxLatitude = min(90, $latitude + $latitudeDelta);
        $minLongitude = $longitude - $longitudeDelta;
        $maxLongitude = $longitude + $longitudeDelta;

        $query->whereHas('location', function (Builder $locationQuery) use (
            $minLatitude,
            $maxLatitude,
            $minLongitude,
            $maxLongitude
        ): void {
            $locationQuery
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->whereBetween('latitude', [$minLatitude, $maxLatitude]);

            if ($minLongitude < -180 || $maxLongitude > 180) {
                $normalizedMin = $this->normalizeLongitude($minLongitude);
                $normalizedMax = $this->normalizeLongitude($maxLongitude);

                $locationQuery->where(function (Builder $longitudeQuery) use ($normalizedMin, $normalizedMax): void {
                    $longitudeQuery
                        ->whereBetween('longitude', [-180, $normalizedMax])
                        ->orWhereBetween('longitude', [$normalizedMin, 180]);
                });

                return;
            }

            $locationQuery->whereBetween('longitude', [$minLongitude, $maxLongitude]);
        });
    }

    private function normalizeLongitude(float $longitude): float
    {
        while ($longitude < -180) {
            $longitude += 360;
        }

        while ($longitude > 180) {
            $longitude -= 360;
        }

        return $longitude;
    }

    /**
     * @param  array{
     *     latitude: float|null,
     *     longitude: float|null,
     *     has_coordinates: bool,
     *     distance_meters: int,
     *     city: string|null,
     *     locality: string|null,
     *     turf_name: string|null,
     *     sport_ids: list<int>,
     *     amenity_ids: list<int>,
     *     is_indoor: bool|null,
     *     min_price_minor: int|null,
     *     max_price_minor: int|null,
     *     date: string|null,
     *     sort: string,
     *     page: int,
     *     per_page: int
     * }  $searchContext
     * @return array<string, mixed>
     */
    private function serializeSearchResult(Turf $turf, array $searchContext): array
    {
        $distanceMeters = null;

        if ($searchContext['has_coordinates'] && $turf->location->latitude !== null && $turf->location->longitude !== null) {
            $distanceMeters = $this->distanceMeters(
                $searchContext['latitude'],
                $searchContext['longitude'],
                $turf->location->latitude,
                $turf->location->longitude,
            );
        }

        $availabilitySummary = $searchContext['date'] !== null
            ? $this->availabilitySummary($turf, $searchContext['date'])
            : null;

        return [
            'id' => $turf->id,
            'name' => $turf->name,
            'description' => $turf->description,
            'surface_type' => $turf->surface_type,
            'is_indoor' => $turf->is_indoor,
            'capacity_count' => $turf->capacity_count,
            'distance_meters' => $distanceMeters,
            'location' => [
                'id' => $turf->location->id,
                'name' => $turf->location->name,
                'locality' => $turf->location->locality,
                'city' => $turf->location->city,
                'state' => $turf->location->state,
                'timezone' => $turf->location->timezone,
                'latitude' => $turf->location->latitude,
                'longitude' => $turf->location->longitude,
            ],
            'sports' => $turf->sports
                ->where('is_active', true)
                ->sortBy('name')
                ->values()
                ->map(fn (Sport $sport): array => [
                    'id' => $sport->id,
                    'name' => $sport->name,
                    'code' => $sport->code,
                ])
                ->all(),
            'amenities' => $this->collectAmenities($turf)->all(),
            'pricing_summary' => $this->pricingSummary($turf),
            'availability_summary' => $availabilitySummary,
            'primary_image' => $turf->images->first() !== null ? [
                'id' => $turf->images->first()->id,
                'caption' => $turf->images->first()->caption,
                'alt_text' => $turf->images->first()->alt_text,
                'original_name' => $turf->images->first()->file?->original_name,
            ] : null,
            'detail_url' => route('customer.turfs.show', $turf),
            'rating' => null,
            'popularity' => null,
        ];
    }

    /**
     * @return Collection<int, array{id: int, name: string, code: string}>
     */
    private function collectAmenities(Turf $turf): Collection
    {
        /** @var Collection<int, Amenity> $amenities */
        $amenities = $turf->amenities
            ->concat($turf->location->amenities)
            ->where('is_active', true)
            ->unique('id')
            ->sortBy('name')
            ->values();

        return $amenities->map(fn (Amenity $amenity): array => [
            'id' => $amenity->id,
            'name' => $amenity->name,
            'code' => $amenity->code,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function pricingSummary(Turf $turf): array
    {
        /** @var Collection<int, PricingRule> $rules */
        $rules = $turf->pricingRules
            ->where('is_active', true)
            ->sortBy('price_minor')
            ->values();

        $first = $rules->first();
        $last = $rules->last();

        return [
            'currency' => $first?->currency,
            'starting_price_minor' => $first?->price_minor,
            'highest_price_minor' => $last?->price_minor,
            'starting_price' => $first !== null ? number_format($first->price_minor / 100, 2, '.', '') : null,
            'highest_price' => $last !== null ? number_format($last->price_minor / 100, 2, '.', '') : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function availabilitySummary(Turf $turf, string $date): array
    {
        $slots = $this->availability->slotsForDate($turf, $date, CarbonImmutable::now('UTC'));
        $first = $slots[0] ?? null;
        $last = $slots === [] ? null : $slots[array_key_last($slots)];

        return [
            'date' => $date,
            'has_availability' => $slots !== [],
            'available_slots_count' => count($slots),
            'first_slot' => $first !== null ? [
                'starts_at' => $first->toArray()['starts_at'],
                'starts_at_time' => substr($first->toArray()['starts_at_time'], 0, 5),
                'ends_at_time' => substr($first->toArray()['ends_at_time'], 0, 5),
            ] : null,
            'last_slot' => $last !== null ? [
                'starts_at' => $last->toArray()['starts_at'],
                'starts_at_time' => substr($last->toArray()['starts_at_time'], 0, 5),
                'ends_at_time' => substr($last->toArray()['ends_at_time'], 0, 5),
            ] : null,
            'sample_slots' => collect($slots)
                ->take(6)
                ->map(fn ($slot): array => [
                    'starts_at' => $slot->toArray()['starts_at'],
                    'ends_at' => $slot->toArray()['ends_at'],
                    'starts_at_time' => substr($slot->toArray()['starts_at_time'], 0, 5),
                    'ends_at_time' => substr($slot->toArray()['ends_at_time'], 0, 5),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $results
     * @return Collection<int, array<string, mixed>>
     */
    private function sortResults(Collection $results, string $sort, bool $hasCoordinates): Collection
    {
        return match ($sort) {
            'distance' => $results->sort(fn (array $left, array $right): int => $this->compareNullableInts(
                $left['distance_meters'],
                $right['distance_meters'],
                $left['id'],
                $right['id'],
            ))->values(),
            'lowest_price' => $results->sort(fn (array $left, array $right): int => $this->compareNullableInts(
                $left['pricing_summary']['starting_price_minor'] ?? null,
                $right['pricing_summary']['starting_price_minor'] ?? null,
                $left['id'],
                $right['id'],
            ))->values(),
            'rating' => $results->sort(fn (array $left, array $right): int => strcasecmp($left['name'], $right['name']) ?: ($left['id'] <=> $right['id']))->values(),
            'popularity' => $results->sort(fn (array $left, array $right): int => strcasecmp($left['name'], $right['name']) ?: ($left['id'] <=> $right['id']))->values(),
            default => $hasCoordinates
                ? $results->sort(fn (array $left, array $right): int => $this->compareNullableInts(
                    $left['distance_meters'],
                    $right['distance_meters'],
                    $left['id'],
                    $right['id'],
                ))->values()
                : $results->sort(fn (array $left, array $right): int => strcasecmp($left['name'], $right['name']) ?: ($left['id'] <=> $right['id']))->values(),
        };
    }

    private function compareNullableInts(?int $left, ?int $right, int $leftId, int $rightId): int
    {
        if ($left === null && $right === null) {
            return $leftId <=> $rightId;
        }

        if ($left === null) {
            return 1;
        }

        if ($right === null) {
            return -1;
        }

        return $left <=> $right ?: ($leftId <=> $rightId);
    }

    private function distanceMeters(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): int
    {
        $latitudeDelta = deg2rad($toLatitude - $fromLatitude);
        $longitudeDelta = deg2rad($toLongitude - $fromLongitude);
        $fromLatitudeRadians = deg2rad($fromLatitude);
        $toLatitudeRadians = deg2rad($toLatitude);

        $a = sin($latitudeDelta / 2) ** 2
            + cos($fromLatitudeRadians) * cos($toLatitudeRadians) * sin($longitudeDelta / 2) ** 2;
        $clamped = min(1.0, max(0.0, $a));

        return (int) round(2 * self::EARTH_RADIUS_METERS * asin(sqrt($clamped)));
    }
}
