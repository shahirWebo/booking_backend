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
        ];
    }

    /**
     * @return array{name: string, code: string, description: string|null}
     */
    public function sportAttributes(): array
    {
        return [
            'name' => (string) $this->input('name'),
            'code' => (string) $this->input('code'),
            'description' => $this->filled('description')
                ? (string) $this->input('description')
                : null,
        ];
    }
}
