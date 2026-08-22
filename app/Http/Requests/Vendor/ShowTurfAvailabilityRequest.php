<?php

namespace App\Http\Requests\Vendor;

use App\Models\Turf;
use Illuminate\Foundation\Http\FormRequest;

final class ShowTurfAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $turf = $this->route('turf');

        return $turf instanceof Turf
            && $this->user()?->can('view', $turf) === true;
    }

    /**
     * @return array<string, list<string|object>>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
        ];
    }

    public function availabilityDate(): string
    {
        return (string) $this->validated('date');
    }
}
