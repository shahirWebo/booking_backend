<?php

namespace App\Http\Requests\Vendor;

use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateVendorPrimaryContactRequest extends FormRequest
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
            'primary_contact_name' => ['required', 'string', 'max:160'],
            'primary_contact_email' => ['required', 'email:rfc', 'max:254'],
            'primary_contact_mobile_number' => ['required', 'regex:/^\\+[1-9]\\d{7,14}$/'],
        ];
    }

    /**
     * @return array{primary_contact_name: string, primary_contact_email: string, primary_contact_mobile_number: string}
     */
    public function primaryContact(): array
    {
        return [
            'primary_contact_name' => trim((string) $this->input('primary_contact_name')),
            'primary_contact_email' => strtolower(trim((string) $this->input('primary_contact_email'))),
            'primary_contact_mobile_number' => trim((string) $this->input('primary_contact_mobile_number')),
        ];
    }
}
