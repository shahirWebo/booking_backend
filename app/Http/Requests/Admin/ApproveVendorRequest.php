<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class ApproveVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
