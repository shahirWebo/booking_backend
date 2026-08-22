<?php

namespace App\Domain\Pricing\Actions;

use App\Domain\Pricing\Repositories\PricingRuleRepository;
use App\Models\PricingRule;
use App\Models\Turf;
use InvalidArgumentException;

final class ManageTurfPricingRulesAction
{
    public function __construct(private readonly PricingRuleRepository $pricingRules) {}

    /** @param array<string, mixed> $attributes */
    public function create(Turf $turf, array $attributes): PricingRule
    {
        return $this->pricingRules->create($turf, $attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function update(Turf $turf, PricingRule $rule, array $attributes): PricingRule
    {
        $this->ensureBelongsToTurf($turf, $rule);

        return $this->pricingRules->update($rule, $attributes);
    }

    public function delete(Turf $turf, PricingRule $rule): void
    {
        $this->ensureBelongsToTurf($turf, $rule);
        $this->pricingRules->delete($rule);
    }

    private function ensureBelongsToTurf(Turf $turf, PricingRule $rule): void
    {
        if ($rule->turf_id !== $turf->id) {
            throw new InvalidArgumentException('The pricing rule does not belong to this turf.');
        }
    }
}
