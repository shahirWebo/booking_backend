<?php

namespace App\Http\Requests\Vendor;

use App\Models\Turf;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

final class StoreTurfSlotBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        $turf = $this->route('turf');

        return $turf instanceof Turf && $this->user()?->can('update', $turf) === true;
    }

    /** @return array<string, list<string|object>> */
    public function rules(): array
    {
        return [
            'block_date' => ['required', 'date_format:Y-m-d'],
            'is_full_day' => ['required', 'boolean'],
            'starts_at_time' => ['nullable', 'required_if:is_full_day,false', 'date_format:H:i'],
            'ends_at_time' => ['nullable', 'required_if:is_full_day,false', 'date_format:H:i'],
            'ends_next_day' => ['required', 'boolean'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return list<callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->boolean('is_full_day') || $validator->errors()->isNotEmpty()) {
                return;
            }

            $start = (string) $this->input('starts_at_time');
            $end = (string) $this->input('ends_at_time');
            $valid = $this->boolean('ends_next_day') ? $end < $start : $end > $start;

            if (! $valid) {
                $validator->errors()->add('ends_at_time', 'The end time must follow the start time, accounting for overnight blocks.');
            }
        }];
    }

    /** @return array<string, mixed> */
    public function blockAttributes(): array
    {
        $fullDay = $this->boolean('is_full_day');

        return [
            'block_date' => (string) $this->validated('block_date'),
            'is_full_day' => $fullDay,
            'starts_at_time' => $fullDay ? null : $this->validated('starts_at_time').':00',
            'ends_at_time' => $fullDay ? null : $this->validated('ends_at_time').':00',
            'ends_next_day' => $fullDay ? false : $this->boolean('ends_next_day'),
            'reason' => filled($this->validated('reason')) ? trim((string) $this->validated('reason')) : null,
        ];
    }
}
