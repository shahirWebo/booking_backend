<?php

namespace App\Http\Requests\Vendor;

use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateVendorGstDetailsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('gstin')) {
            $this->merge([
                'gstin' => strtoupper(trim((string) $this->input('gstin'))),
            ]);
        }
    }

    public function authorize(): bool
    {
        $vendor = $this->route('vendor');

        return $vendor instanceof Vendor
            && $this->user()?->can('update', $vendor) === true;
    }

    /**
     * @return array<string, list<string|object>>
     */
    public function rules(): array
    {
        return [
            'is_gst_registered' => ['required', 'boolean'],
            'gstin' => [
                'nullable',
                Rule::requiredIf($this->boolean('is_gst_registered')),
                Rule::prohibitedIf(! $this->boolean('is_gst_registered')),
                'string',
                'size:15',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/',
            ],
        ];
    }

    /**
     * @return array{is_gst_registered: bool, gstin: string|null}
     */
    public function gstDetails(): array
    {
        $isGstRegistered = $this->boolean('is_gst_registered');

        return [
            'is_gst_registered' => $isGstRegistered,
            'gstin' => $isGstRegistered
                ? (string) $this->input('gstin')
                : null,
        ];
    }
}
