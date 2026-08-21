<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Admin;

use App\Models\Sport;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

final class UpdateSportRequest extends FormRequest
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
        /** @var Sport $sport */
        $sport = $this->route('sport');

        return [
            'name' => ['bail', 'required', 'string', 'max:255', Rule::unique('sports', 'name')->ignore($sport->id)],
            'code' => ['bail', 'required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:_[a-z0-9]+)*$/', Rule::unique('sports', 'code')->ignore($sport->id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'icon_asset_key' => ['nullable', 'string', 'max:255'],
            'icon_alt_text' => ['nullable', 'string', 'max:255'],
            'image_asset_key' => ['nullable', 'string', 'max:255'],
            'image_alt_text' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array{name: string, code: string, description: string|null, is_active?: bool, icon_asset_key: string|null, icon_alt_text: string|null, image_asset_key: string|null, image_alt_text: string|null}
     */
    public function sportAttributes(): array
    {
        $attributes = [
            'name' => (string) $this->input('name'),
            'code' => (string) $this->input('code'),
            'description' => $this->filled('description')
                ? (string) $this->input('description')
                : null,
            'icon_asset_key' => $this->filled('icon_asset_key')
                ? (string) $this->input('icon_asset_key')
                : null,
            'icon_alt_text' => $this->filled('icon_alt_text')
                ? (string) $this->input('icon_alt_text')
                : null,
            'image_asset_key' => $this->filled('image_asset_key')
                ? (string) $this->input('image_asset_key')
                : null,
            'image_alt_text' => $this->filled('image_alt_text')
                ? (string) $this->input('image_alt_text')
                : null,
        ];

        if ($this->has('is_active')) {
            $attributes['is_active'] = $this->boolean('is_active');
        }

        return $attributes;
    }
}
