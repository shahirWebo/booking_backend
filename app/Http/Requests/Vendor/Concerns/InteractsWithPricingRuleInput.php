<?php

namespace App\Http\Requests\Vendor\Concerns;

use App\Domain\Pricing\Enums\PricingRuleType;
use Illuminate\Contracts\Validation\Validator;

trait InteractsWithPricingRuleInput
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'rule_type' => filled($this->input('rule_type')) ? trim((string) $this->input('rule_type')) : null,
            'currency' => filled($this->input('currency')) ? strtoupper(trim((string) $this->input('currency'))) : null,
            'effective_from_date' => filled($this->input('effective_from_date')) ? $this->input('effective_from_date') : null,
            'effective_until_date' => filled($this->input('effective_until_date')) ? $this->input('effective_until_date') : null,
            'special_date' => filled($this->input('special_date')) ? $this->input('special_date') : null,
            'starts_at_time' => $this->normalizeTime($this->input('starts_at_time')),
            'ends_at_time' => $this->normalizeTime($this->input('ends_at_time')),
        ]);
    }

    /**
     * @return array<string, list<string|object>>
     */
    protected function pricingRuleRules(): array
    {
        return [
            'rule_type' => ['required', 'string', 'in:'.implode(',', array_column(PricingRuleType::cases(), 'value'))],
            'price' => ['required', 'string', 'regex:/^(?:0|[1-9]\d*)(?:\.\d{1,2})?$/'],
            'currency' => ['required', 'regex:/^[A-Z]{3}$/'],
            'priority' => ['required', 'integer', 'between:1,65535'],
            'effective_from_date' => ['nullable', 'date_format:Y-m-d'],
            'effective_until_date' => ['nullable', 'date_format:Y-m-d'],
            'weekday' => ['nullable', 'integer', 'between:1,7'],
            'special_date' => ['nullable', 'date_format:Y-m-d'],
            'starts_at_time' => ['nullable', 'date_format:H:i:s'],
            'ends_at_time' => ['nullable', 'date_format:H:i:s'],
            'ends_next_day' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function validatePricingRule(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $ruleType = PricingRuleType::from((string) $this->input('rule_type'));
        $weekday = $this->input('weekday');
        $specialDate = $this->input('special_date');
        $startsAtTime = $this->input('starts_at_time');
        $endsAtTime = $this->input('ends_at_time');
        $endsNextDay = $this->boolean('ends_next_day');

        if ($this->priceMinor() > 900000000000000) {
            $validator->errors()->add('price', 'The price is too large.');
        }

        if ($this->input('effective_from_date') !== null
            && $this->input('effective_until_date') !== null
            && $this->input('effective_until_date') < $this->input('effective_from_date')) {
            $validator->errors()->add('effective_until_date', 'The effective end date must not be before the effective start date.');
        }

        $selectorsAreValid = match ($ruleType) {
            PricingRuleType::Base, PricingRuleType::Weekend, PricingRuleType::PeakHour => $weekday === null && $specialDate === null,
            PricingRuleType::Weekday => $weekday !== null && $specialDate === null,
            PricingRuleType::SpecialDate => $weekday === null && $specialDate !== null,
        };

        if (! $selectorsAreValid) {
            $validator->errors()->add('rule_type', 'The selected rule type does not match its weekday or special-date selector.');
        }

        if ($ruleType !== PricingRuleType::PeakHour) {
            if ($startsAtTime !== null || $endsAtTime !== null || $endsNextDay) {
                $validator->errors()->add('starts_at_time', 'Only peak-hour rules can define a time window.');
            }

            return;
        }

        if ($startsAtTime === null || $endsAtTime === null) {
            $validator->errors()->add('starts_at_time', 'Peak-hour rules require both a start and end time.');

            return;
        }

        $validWindow = $endsNextDay ? $endsAtTime < $startsAtTime : $endsAtTime > $startsAtTime;

        if (! $validWindow) {
            $validator->errors()->add('ends_at_time', 'The end time must follow the start time, accounting for overnight pricing.');
        }
    }

    /** @return array<string, mixed> */
    public function pricingRuleAttributes(): array
    {
        return [
            'rule_type' => $this->validated('rule_type'),
            'price_minor' => $this->priceMinor(),
            'currency' => $this->validated('currency'),
            'priority' => (int) $this->validated('priority'),
            'effective_from_date' => $this->validated('effective_from_date'),
            'effective_until_date' => $this->validated('effective_until_date'),
            'weekday' => $this->validated('weekday'),
            'special_date' => $this->validated('special_date'),
            'starts_at_time' => $this->validated('starts_at_time'),
            'ends_at_time' => $this->validated('ends_at_time'),
            'ends_next_day' => $this->boolean('ends_next_day'),
            'is_active' => $this->boolean('is_active'),
        ];
    }

    private function normalizeTime(mixed $time): ?string
    {
        if (! filled($time)) {
            return null;
        }

        $value = trim((string) $time);

        return strlen($value) === 5 ? "$value:00" : $value;
    }

    private function priceMinor(): int
    {
        [$whole, $fraction] = array_pad(explode('.', (string) $this->validated('price'), 2), 2, '');

        return (int) ($whole.str_pad($fraction, 2, '0'));
    }
}
