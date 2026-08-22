<?php

namespace App\Http\Requests\Vendor;

use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;

final class SubmitVendorOnboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $vendor = $this->route('vendor');

        return $vendor instanceof Vendor
            && $this->user()?->can('manageStaff', $vendor) === true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'submission_version' => ['required', 'integer', 'min:1'],
        ];
    }

    public function submissionVersion(): int
    {
        return (int) $this->input('submission_version');
    }
}
