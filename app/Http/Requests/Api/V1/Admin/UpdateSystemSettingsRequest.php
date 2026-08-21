<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateSystemSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'booking' => ['bail', 'required', 'array'],
            'booking.booking_hold_minutes' => ['bail', 'required', 'integer', 'min:1', 'max:60'],
            'booking.cancellation_cutoff_hours' => ['bail', 'required', 'integer', 'min:0', 'max:168'],
            'booking.max_advance_booking_days' => ['bail', 'required', 'integer', 'min:1', 'max:365'],
            'booking.min_slot_duration_minutes' => ['bail', 'required', 'integer', 'min:30', 'max:240'],
            'booking.max_booking_duration_minutes' => ['bail', 'required', 'integer', 'min:30', 'max:600'],
            'otp' => ['bail', 'required', 'array'],
            'otp.code_lifetime_seconds' => ['bail', 'required', 'integer', 'min:60', 'max:900'],
            'otp.resend_cooldown_seconds' => ['bail', 'required', 'integer', 'min:30', 'max:300'],
            'otp.max_verification_attempts' => ['bail', 'required', 'integer', 'min:1', 'max:10'],
            'support' => ['bail', 'required', 'array'],
            'support.support_email' => ['bail', 'required', 'email', 'max:255'],
            'support.support_phone_e164' => ['bail', 'required', 'string', 'regex:/^\\+[1-9]\\d{7,14}$/'],
            'support.support_hours' => ['bail', 'required', 'string', 'max:255'],
            'support.support_timezone' => ['bail', 'required', 'timezone:all'],
        ];
    }

    /**
     * @return array{
     *     booking: array<string, int>,
     *     otp: array<string, int>,
     *     support: array<string, string>
     * }
     */
    public function settingsAttributes(): array
    {
        return [
            'booking' => [
                'booking_hold_minutes' => $this->integer('booking.booking_hold_minutes'),
                'cancellation_cutoff_hours' => $this->integer('booking.cancellation_cutoff_hours'),
                'max_advance_booking_days' => $this->integer('booking.max_advance_booking_days'),
                'min_slot_duration_minutes' => $this->integer('booking.min_slot_duration_minutes'),
                'max_booking_duration_minutes' => $this->integer('booking.max_booking_duration_minutes'),
            ],
            'otp' => [
                'code_lifetime_seconds' => $this->integer('otp.code_lifetime_seconds'),
                'resend_cooldown_seconds' => $this->integer('otp.resend_cooldown_seconds'),
                'max_verification_attempts' => $this->integer('otp.max_verification_attempts'),
            ],
            'support' => [
                'support_email' => (string) $this->input('support.support_email'),
                'support_phone_e164' => (string) $this->input('support.support_phone_e164'),
                'support_hours' => (string) $this->input('support.support_hours'),
                'support_timezone' => (string) $this->input('support.support_timezone'),
            ],
        ];
    }
}
