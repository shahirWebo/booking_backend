<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RejectVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'submission_version' => ['required', 'integer', 'min:1'],
            'reason_code' => ['required', 'string', Rule::in([
                'business_information_mismatch',
                'document_verification_required',
                'incomplete_submission',
                'other',
            ])],
            'reason_message' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    public function submissionVersion(): int
    {
        return (int) $this->input('submission_version');
    }

    public function reasonCode(): string
    {
        return (string) $this->input('reason_code');
    }

    public function reasonMessage(): string
    {
        return trim((string) $this->input('reason_message'));
    }
}
