<?php

namespace App\Http\Requests\Vendor;

use App\Domain\Locations\Enums\LocationStatus;
use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateVendorLocationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $location = $this->route('location');

        return $location instanceof Location
            && $this->user()?->can('update', $location) === true;
    }

    /**
     * @return array<string, list<string|object>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(LocationStatus::class)],
        ];
    }

    public function status(): LocationStatus
    {
        return LocationStatus::from((string) $this->input('status'));
    }
}
