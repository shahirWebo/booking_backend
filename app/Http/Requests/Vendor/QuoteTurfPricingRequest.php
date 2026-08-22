<?php

namespace App\Http\Requests\Vendor;

use App\Models\Turf;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

final class QuoteTurfPricingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $turf = $this->route('turf');

        return $turf instanceof Turf && $this->user()?->can('view', $turf) === true;
    }

    /** @return array<string, list<string|object>> */
    public function rules(): array
    {
        return [
            'slots' => ['required', 'array', 'min:1', 'max:100'],
            'slots.*.starts_at' => ['required', 'date', 'regex:/^.+(Z|[+-]\\d{2}:\\d{2})$/'],
            'slots.*.ends_at' => ['required', 'date', 'regex:/^.+(Z|[+-]\\d{2}:\\d{2})$/'],
        ];
    }

    /**
     * @return list<array{starts_at: CarbonImmutable, ends_at: CarbonImmutable}>
     */
    public function slots(): array
    {
        return array_values(array_map(
            fn (array $slot): array => [
                'starts_at' => CarbonImmutable::parse($slot['starts_at'])->utc(),
                'ends_at' => CarbonImmutable::parse($slot['ends_at'])->utc(),
            ],
            $this->validated('slots'),
        ));
    }
}
