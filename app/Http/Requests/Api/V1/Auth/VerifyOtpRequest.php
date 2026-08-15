<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'otp_request_id' => ['bail', 'required', 'string', 'ulid'],
            'code' => ['bail', 'required', 'string', 'regex:/^\d{6}$/'],
        ];
    }
}
