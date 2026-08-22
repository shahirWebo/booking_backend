<?php

namespace App\Http\Requests\Vendor;

use App\Http\Requests\Vendor\Concerns\InteractsWithLocationPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreVendorLocationRequest extends FormRequest
{
    use InteractsWithLocationPayload;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<string|object>>
     */
    public function rules(): array
    {
        return $this->baseRules();
    }

    /**
     * @return array<string, list<string|object>>
     */
    private function baseRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'locality' => ['nullable', 'string', 'max:150'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'max:120'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country_code' => ['required', 'string', 'size:2'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'timezone' => ['required', 'timezone:all'],
            'amenity_ids' => ['sometimes', 'array'],
            'amenity_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('amenities', 'id')->where('is_active', true),
            ],
            'operating_hours' => ['sometimes', 'array'],
            'operating_hours.*.weekday' => ['required', 'integer', 'between:1,7'],
            'operating_hours.*.opens_at_time' => ['required', 'date_format:H:i'],
            'operating_hours.*.closes_at_time' => ['required', 'date_format:H:i'],
            'operating_hours.*.ends_next_day' => ['sometimes', 'boolean'],
            'images' => ['sometimes', 'array'],
            'images.*.file_id' => ['required', 'integer', 'distinct'],
            'images.*.caption' => ['nullable', 'string', 'max:255'],
            'images.*.alt_text' => ['nullable', 'string', 'max:255'],
        ];
    }
}
