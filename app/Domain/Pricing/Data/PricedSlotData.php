<?php

namespace App\Domain\Pricing\Data;

use Carbon\CarbonImmutable;

final readonly class PricedSlotData
{
    public function __construct(
        public CarbonImmutable $startsAt,
        public CarbonImmutable $endsAt,
        public int $priceMinor,
        public string $currency,
        public int $pricingRuleId,
    ) {}

    /**
     * @return array{starts_at: string, ends_at: string, price_minor: int, currency: string, pricing_rule_id: int}
     */
    public function toArray(): array
    {
        return [
            'starts_at' => $this->startsAt->utc()->format('Y-m-d\\TH:i:s.u\\Z'),
            'ends_at' => $this->endsAt->utc()->format('Y-m-d\\TH:i:s.u\\Z'),
            'price_minor' => $this->priceMinor,
            'currency' => $this->currency,
            'pricing_rule_id' => $this->pricingRuleId,
        ];
    }
}
