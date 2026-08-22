<?php

namespace App\Http\Requests\Vendor\Concerns;

use App\Domain\Files\Enums\FilePurpose;
use App\Domain\Files\Enums\FileStatus;
use App\Models\File;
use App\Models\Location;
use App\Models\Vendor;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

trait InteractsWithLocationPayload
{
    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $validator->errors()->isEmpty()) {
                return;
            }

            $this->validateCoordinates($validator);
            $this->validateOperatingHours($validator);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function locationAttributes(): array
    {
        return [
            'name' => trim((string) $this->input('name')),
            'address_line_1' => trim((string) $this->input('address_line_1')),
            'address_line_2' => $this->trimmedNullable('address_line_2'),
            'landmark' => $this->trimmedNullable('landmark'),
            'locality' => $this->trimmedNullable('locality'),
            'city' => trim((string) $this->input('city')),
            'state' => trim((string) $this->input('state')),
            'postal_code' => trim((string) $this->input('postal_code')),
            'country_code' => strtoupper(trim((string) $this->input('country_code'))),
            'latitude' => $this->filled('latitude') ? (float) $this->input('latitude') : null,
            'longitude' => $this->filled('longitude') ? (float) $this->input('longitude') : null,
            'timezone' => trim((string) $this->input('timezone')),
        ];
    }

    /**
     * @return list<array{weekday: int, opens_at_time: string, closes_at_time: string, ends_next_day: bool}>
     */
    public function operatingHours(): array
    {
        $windows = [];

        foreach ($this->input('operating_hours', []) as $window) {
            if (! is_array($window)) {
                continue;
            }

            $windows[] = [
                'weekday' => (int) ($window['weekday'] ?? 0),
                'opens_at_time' => trim((string) ($window['opens_at_time'] ?? '')),
                'closes_at_time' => trim((string) ($window['closes_at_time'] ?? '')),
                'ends_next_day' => (bool) ($window['ends_next_day'] ?? false),
            ];
        }

        usort($windows, function (array $left, array $right): int {
            return [$left['weekday'], $left['opens_at_time']] <=> [$right['weekday'], $right['opens_at_time']];
        });

        return $windows;
    }

    /**
     * @return list<int>
     */
    public function amenityIds(): array
    {
        $amenityIds = [];

        foreach ($this->input('amenity_ids', []) as $amenityId) {
            $amenityIds[] = (int) $amenityId;
        }

        return $amenityIds;
    }

    /**
     * @return list<array{file: File, caption: string|null, alt_text: string|null}>
     */
    public function locationImages(Vendor $vendor, ?Location $currentLocation = null): array
    {
        $images = [];

        foreach ($this->input('images', []) as $index => $image) {
            if (! is_array($image)) {
                continue;
            }

            $file = File::query()
                ->whereKey((int) ($image['file_id'] ?? 0))
                ->where('vendor_id', $vendor->id)
                ->where('purpose', FilePurpose::LocationImage->value)
                ->where('status', FileStatus::Ready->value)
                ->first();

            if (! $file instanceof File) {
                throw ValidationException::withMessages([
                    "images.$index.file_id" => 'Select a ready location image owned by this vendor.',
                ]);
            }

            $existingImageQuery = $file->locationImages();

            if ($currentLocation instanceof Location) {
                $existingImageQuery->where('location_id', '!=', $currentLocation->id);
            }

            $existingLocationId = $existingImageQuery->value('location_id');

            if ($existingLocationId !== null) {
                throw ValidationException::withMessages([
                    "images.$index.file_id" => 'This image is already attached to another location.',
                ]);
            }

            $images[] = [
                'file' => $file,
                'caption' => $this->trimmedNullableFrom($image, 'caption'),
                'alt_text' => $this->trimmedNullableFrom($image, 'alt_text'),
            ];
        }

        return $images;
    }

    protected function validateCoordinates(Validator $validator): void
    {
        $latitudeFilled = $this->filled('latitude');
        $longitudeFilled = $this->filled('longitude');

        if ($latitudeFilled xor $longitudeFilled) {
            $validator->errors()->add('latitude', 'Latitude and longitude must be provided together.');
            $validator->errors()->add('longitude', 'Latitude and longitude must be provided together.');
        }
    }

    protected function validateOperatingHours(Validator $validator): void
    {
        $grouped = collect($this->operatingHours())->groupBy('weekday');

        $grouped->each(function (Collection $windows, int $weekday) use ($validator): void {
            $normalized = $windows->map(function (array $window, int $index): array {
                $opensAt = $this->minutesSinceMidnight($window['opens_at_time']);
                $closesAt = $this->minutesSinceMidnight($window['closes_at_time']);
                $endsNextDay = $window['ends_next_day'];
                $normalizedClose = $endsNextDay ? $closesAt + 1440 : $closesAt;

                if ((! $endsNextDay && $closesAt <= $opensAt) || ($endsNextDay && $closesAt >= $opensAt)) {
                    return [
                        'index' => $index,
                        'invalid' => true,
                        'start' => $opensAt,
                        'end' => $normalizedClose,
                    ];
                }

                return [
                    'index' => $index,
                    'invalid' => false,
                    'start' => $opensAt,
                    'end' => $normalizedClose,
                ];
            })->sortBy('start')->values();

            foreach ($normalized as $position => $window) {
                if ($window['invalid']) {
                    $validator->errors()->add(
                        "operating_hours.{$window['index']}.closes_at_time",
                        'The closing time must be after the opening time, accounting for next-day windows.',
                    );

                    continue;
                }

                if ($position === 0) {
                    continue;
                }

                $previous = $normalized[$position - 1];

                if ($window['start'] < $previous['end']) {
                    $validator->errors()->add(
                        "operating_hours.{$window['index']}.opens_at_time",
                        sprintf('Operating hours overlap for weekday %d.', $weekday),
                    );
                }
            }
        });
    }

    protected function trimmedNullable(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function trimmedNullableFrom(array $payload, string $key): ?string
    {
        $value = trim((string) ($payload[$key] ?? ''));

        return $value === '' ? null : $value;
    }

    protected function minutesSinceMidnight(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return ($hours * 60) + $minutes;
    }
}
