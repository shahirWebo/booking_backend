<?php

namespace App\Http\Requests\Vendor;

use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateVendorBusinessDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $vendor = $this->route('vendor');

        return $vendor instanceof Vendor
            && $this->user()?->can('update', $vendor) === true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'legal_name' => ['required', 'string', 'max:160'],
            'display_name' => ['required', 'string', 'max:160'],
            'legal_entity_type' => ['required', 'string', 'max:50'],
        ];
    }

    /**
     * @return array{legal_name: string, display_name: string, legal_entity_type: string}
     */
    public function businessDetails(): array
    {
        return [
            'legal_name' => trim((string) $this->input('legal_name')),
            'display_name' => trim((string) $this->input('display_name')),
            'legal_entity_type' => trim((string) $this->input('legal_entity_type')),
        ];
    }
}
