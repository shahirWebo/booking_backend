<?php

namespace App\Http\Requests\Vendor\Concerns;

use App\Domain\Files\Enums\FilePurpose;
use App\Domain\Files\Enums\FileStatus;
use App\Models\File;
use App\Models\Turf;
use App\Models\Vendor;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

trait InteractsWithTurfPayload
{
    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $validator->errors()->isEmpty()) {
                return;
            }

            $this->validateDimensions($validator);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function turfAttributes(): array
    {
        return [
            'name' => trim((string) $this->input('name')),
            'description' => $this->trimmedNullable('description'),
            'surface_type' => $this->trimmedNullable('surface_type'),
            'is_indoor' => $this->filled('is_indoor') ? $this->boolean('is_indoor') : null,
            'capacity_count' => $this->filled('capacity_count') ? (int) $this->input('capacity_count') : null,
            'length_meters' => $this->filled('length_meters') ? round((float) $this->input('length_meters'), 2) : null,
            'width_meters' => $this->filled('width_meters') ? round((float) $this->input('width_meters'), 2) : null,
        ];
    }

    /**
     * @return list<int>
     */
    public function sportIds(): array
    {
        $sportIds = [];

        foreach ($this->input('sport_ids', []) as $sportId) {
            $sportIds[] = (int) $sportId;
        }

        return $sportIds;
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
    public function turfImages(Vendor $vendor, ?Turf $currentTurf = null): array
    {
        $images = [];

        foreach ($this->input('images', []) as $index => $image) {
            if (! is_array($image)) {
                continue;
            }

            $file = File::query()
                ->whereKey((int) ($image['file_id'] ?? 0))
                ->where('vendor_id', $vendor->id)
                ->where('purpose', FilePurpose::TurfImage->value)
                ->where('status', FileStatus::Ready->value)
                ->first();

            if (! $file instanceof File) {
                throw ValidationException::withMessages([
                    "images.$index.file_id" => 'Select a ready turf image owned by this vendor.',
                ]);
            }

            $existingImageQuery = $file->turfImages();

            if ($currentTurf instanceof Turf) {
                $existingImageQuery->where('turf_id', '!=', $currentTurf->id);
            }

            $existingTurfId = $existingImageQuery->value('turf_id');

            if ($existingTurfId !== null) {
                throw ValidationException::withMessages([
                    "images.$index.file_id" => 'This image is already attached to another turf.',
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

    /**
     * @return list<array{title: string, description: string, is_active: bool}>
     */
    public function rulesPayload(): array
    {
        $rules = [];

        foreach ($this->input('rules', []) as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            $rules[] = [
                'title' => trim((string) ($rule['title'] ?? '')),
                'description' => trim((string) ($rule['description'] ?? '')),
                'is_active' => (bool) ($rule['is_active'] ?? true),
            ];
        }

        return $rules;
    }

    protected function validateDimensions(Validator $validator): void
    {
        $lengthFilled = $this->filled('length_meters');
        $widthFilled = $this->filled('width_meters');

        if ($lengthFilled xor $widthFilled) {
            $validator->errors()->add('length_meters', 'Length and width must be provided together.');
            $validator->errors()->add('width_meters', 'Length and width must be provided together.');
        }
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
}
