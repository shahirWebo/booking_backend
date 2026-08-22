<?php

namespace App\Http\Requests\Vendor;

use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;

final class StoreVendorBankAccountRequest extends FormRequest
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
            'account_holder_name' => ['required', 'string', 'max:150'],
            'bank_name' => ['required', 'string', 'max:150'],
            'account_number' => ['required', 'regex:/^\\d{9,18}$/'],
            'routing_code' => ['required', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('routing_code')) {
            $this->merge([
                'routing_code' => strtoupper(trim((string) $this->input('routing_code'))),
            ]);
        }
    }

    /**
     * @return array{account_holder_name: string, bank_name: string, account_number: string, routing_code: string}
     */
    public function bankAccountDetails(): array
    {
        return [
            'account_holder_name' => trim((string) $this->input('account_holder_name')),
            'bank_name' => trim((string) $this->input('bank_name')),
            'account_number' => trim((string) $this->input('account_number')),
            'routing_code' => (string) $this->input('routing_code'),
        ];
    }
}
