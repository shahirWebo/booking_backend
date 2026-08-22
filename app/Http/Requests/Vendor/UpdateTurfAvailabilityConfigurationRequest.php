<?php

namespace App\Http\Requests\Vendor;

use App\Models\Turf;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateTurfAvailabilityConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $turf = $this->route('turf');

        return $turf instanceof Turf && $this->user()?->can('update', $turf) === true;
    }

    /** @return array<string, list<string|object>> */
    public function rules(): array
    {
        $turf = $this->route('turf');
        $minimum = $turf instanceof Turf ? $turf->min_booking_duration_minutes : 15;
        $maximum = $turf instanceof Turf ? $turf->max_booking_duration_minutes : 1440;

        return [
            'default_slot_duration_minutes' => ['required', 'integer', "between:$minimum,$maximum"],
            'booking_lead_time_minutes' => ['required', 'integer', 'min:0', 'max:10080'],
            'advance_booking_window_days' => ['required', 'integer', 'min:1', 'max:365'],
        ];
    }
}
