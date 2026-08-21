<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Admin;

use App\Models\Amenity;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

final class UpdateAmenityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, ValidationRule|Unique|string>>
     */
    public function rules(): array
    {
        /** @var Amenity $amenity */
        $amenity = $this->route('amenity');

        return [
            'name' => ['bail', 'required', 'string', 'max:255', Rule::unique('amenities', 'name')->ignore($amenity->id)],
            'code' => ['bail', 'required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:_[a-z0-9]+)*$/', Rule::unique('amenities', 'code')->ignore($amenity->id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array{name: string, code: string, description: string|null, is_active?: bool}
     */
    public function amenityAttributes(): array
    {
        $attributes = [
            'name' => (string) $this->input('name'),
            'code' => (string) $this->input('code'),
            'description' => $this->filled('description')
                ? (string) $this->input('description')
                : null,
        ];

        if ($this->has('is_active')) {
            $attributes['is_active'] = $this->boolean('is_active');
        }

        return $attributes;
    }
}
