<?php

namespace App\Domain\Pricing\Repositories;

use App\Models\PricingRule;
use App\Models\Turf;
use Illuminate\Database\Eloquent\Collection;

final class PricingRuleRepository
{
    /**
     * @return Collection<int, PricingRule>
     */
    public function activeForTurf(Turf $turf): Collection
    {
        return $turf->pricingRules()
            ->where('is_active', true)
            ->orderBy('priority')
            ->orderBy('id')
            ->get();
    }

    /** @param array<string, mixed> $attributes */
    public function create(Turf $turf, array $attributes): PricingRule
    {
        /** @var PricingRule $rule */
        $rule = $turf->pricingRules()->create($attributes);

        return $rule->refresh();
    }

    /** @param array<string, mixed> $attributes */
    public function update(PricingRule $rule, array $attributes): PricingRule
    {
        $rule->fill($attributes);
        $rule->save();

        return $rule->refresh();
    }

    public function delete(PricingRule $rule): void
    {
        $rule->delete();
    }
}
