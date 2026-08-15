<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Domain\Auth\Services\MobileNumberNormalizer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Throwable;

final class RequestOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mobile' => [
                'bail',
                'required',
                'string',
                'max:64',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    try {
                        app(MobileNumberNormalizer::class)->normalize($value);
                    } catch (Throwable) {
                        $fail('Enter a valid mobile number.');
                    }
                },
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $mobileNumber = $this->input('mobile');

        if (! is_string($mobileNumber)) {
            return;
        }

        try {
            $this->merge([
                'mobile' => app(MobileNumberNormalizer::class)->normalize(trim($mobileNumber)),
            ]);
        } catch (Throwable) {
            $this->merge(['mobile' => trim($mobileNumber)]);
        }
    }
}
