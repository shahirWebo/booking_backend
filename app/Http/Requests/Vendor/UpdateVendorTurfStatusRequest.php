<?php

namespace App\Http\Requests\Vendor;

use App\Domain\Turfs\Enums\TurfStatus;
use App\Models\Turf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateVendorTurfStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $turf = $this->route('turf');

        return $turf instanceof Turf
            && $this->user()?->can('update', $turf) === true;
    }

    /**
     * @return array<string, list<string|object>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(TurfStatus::class)],
        ];
    }

    public function status(): TurfStatus
    {
        return TurfStatus::from((string) $this->input('status'));
    }
}
