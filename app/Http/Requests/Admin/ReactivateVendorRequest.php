<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class ReactivateVendorRequest extends FormRequest
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
            'reason_message' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function submissionVersion(): int
    {
        return (int) $this->input('submission_version');
    }

    public function reasonMessage(): string
    {
        return trim((string) $this->input('reason_message'));
    }
}
