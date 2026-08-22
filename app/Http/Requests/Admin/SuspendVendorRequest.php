<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SuspendVendorRequest extends FormRequest
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
                'compliance_review',
                'operational_risk',
                'policy_violation',
                'suspected_fraud',
            ])],
            'reason_message' => ['required', 'string', 'min:10', 'max:1000'],
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
