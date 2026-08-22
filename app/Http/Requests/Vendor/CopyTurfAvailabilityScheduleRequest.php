<?php

namespace App\Http\Requests\Vendor;

use App\Models\Turf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CopyTurfAvailabilityScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $turf = $this->route('turf');
        $target = Turf::query()->find((int) $this->input('target_turf_id'));

        return $turf instanceof Turf
            && $target instanceof Turf
            && $this->user()->can('update', $turf)
            && $this->user()->can('update', $target);
    }

    /** @return array<string, list<string|object>> */
    public function rules(): array
    {
        $turf = $this->route('turf');

        return [
            'target_turf_id' => ['required', 'integer', Rule::exists('turfs', 'id'), Rule::notIn([$turf instanceof Turf ? $turf->id : 0])],
        ];
    }

    public function target(): Turf
    {
        return Turf::query()->findOrFail((int) $this->validated('target_turf_id'));
    }
}
