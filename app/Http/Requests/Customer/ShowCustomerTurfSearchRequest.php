<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class ShowCustomerTurfSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'city' => ['nullable', 'string', 'max:120'],
            'locality' => ['nullable', 'string', 'max:150'],
            'turf_name' => ['nullable', 'string', 'max:150'],
            'sport_ids' => ['nullable', 'array'],
            'sport_ids.*' => ['integer', 'exists:sports,id'],
            'amenity_ids' => ['nullable', 'array'],
            'amenity_ids.*' => ['integer', 'exists:amenities,id'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'distance_meters' => ['nullable', 'integer', 'min:1', 'max:100000', 'required_with:latitude,longitude'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'is_indoor' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(['recommended', 'distance', 'lowest_price', 'rating', 'popularity'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:24'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->filled('sort') && $this->input('sort') === 'distance' && ! $this->filled('latitude')) {
                $validator->errors()->add('sort', 'Distance sorting requires latitude and longitude.');
            }

            if ($this->filled('min_price') && $this->filled('max_price')
                && (float) $this->input('max_price') < (float) $this->input('min_price')) {
                $validator->errors()->add('max_price', 'The maximum price must be greater than or equal to the minimum price.');
            }
        });
    }
}
