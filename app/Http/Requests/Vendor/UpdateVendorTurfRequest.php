<?php

namespace App\Http\Requests\Vendor;

use App\Http\Requests\Vendor\Concerns\InteractsWithTurfPayload;
use App\Models\Turf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateVendorTurfRequest extends FormRequest
{
    use InteractsWithTurfPayload;

    public function authorize(): bool
    {
        $turf = $this->route('turf');

        return $turf instanceof Turf
            && $this->user()?->can('update', $turf) === true;
    }

    /**
     * @return array<string, list<string|object>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'surface_type' => ['nullable', 'string', 'max:50'],
            'is_indoor' => ['nullable', 'boolean'],
            'capacity_count' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'length_meters' => ['nullable', 'numeric', 'gt:0'],
            'width_meters' => ['nullable', 'numeric', 'gt:0'],
            'sport_ids' => ['sometimes', 'array'],
            'sport_ids.*' => ['integer', 'distinct', Rule::exists('sports', 'id')->where('is_active', true)],
            'amenity_ids' => ['sometimes', 'array'],
            'amenity_ids.*' => ['integer', 'distinct', Rule::exists('amenities', 'id')->where('is_active', true)],
            'images' => ['sometimes', 'array'],
            'images.*.file_id' => ['required', 'integer', 'distinct'],
            'images.*.caption' => ['nullable', 'string', 'max:255'],
            'images.*.alt_text' => ['nullable', 'string', 'max:255'],
            'rules' => ['sometimes', 'array'],
            'rules.*.title' => ['required', 'string', 'max:120'],
            'rules.*.description' => ['required', 'string'],
            'rules.*.is_active' => ['sometimes', 'boolean'],
        ];
    }
}
