<?php

namespace App\Domain\Pricing\Data;

final readonly class PricingQuoteData
{
    /** @param list<PricedSlotData> $slots */
    public function __construct(
        public array $slots,
        public int $totalMinor,
        public string $currency,
    ) {}

    /**
     * @return array{slots: list<array{starts_at: string, ends_at: string, price_minor: int, currency: string, pricing_rule_id: int}>, total_minor: int, currency: string}
     */
    public function toArray(): array
    {
        return [
            'slots' => array_map(fn (PricedSlotData $slot): array => $slot->toArray(), $this->slots),
            'total_minor' => $this->totalMinor,
            'currency' => $this->currency,
        ];
    }
}
