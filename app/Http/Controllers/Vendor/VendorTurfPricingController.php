<?php

namespace App\Http\Controllers\Vendor;

use App\Domain\Pricing\Actions\ManageTurfPricingRulesAction;
use App\Domain\Pricing\Services\PricingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\QuoteTurfPricingRequest;
use App\Http\Requests\Vendor\StoreTurfPricingRuleRequest;
use App\Http\Requests\Vendor\UpdateTurfPricingRuleRequest;
use App\Models\PricingRule;
use App\Models\Turf;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final class VendorTurfPricingController extends Controller
{
    public function __construct(
        private readonly ManageTurfPricingRulesAction $managePricingRules,
        private readonly PricingService $pricing,
    ) {}

    public function index(Turf $turf): JsonResponse
    {
        Gate::authorize('view', $turf);

        return response()->json([
            'pricing_rules' => $turf->pricingRules
                ->map(fn (PricingRule $rule): array => $this->serializeRule($rule))
                ->values()
                ->all(),
        ]);
    }

    public function store(StoreTurfPricingRuleRequest $request, Turf $turf): RedirectResponse
    {
        $this->managePricingRules->create($turf, $request->pricingRuleAttributes());

        return back();
    }

    public function update(UpdateTurfPricingRuleRequest $request, Turf $turf, PricingRule $pricingRule): RedirectResponse
    {
        Gate::authorize('update', $pricingRule->turf);
        abort_unless($pricingRule->turf_id === $turf->id, 404);
        $this->managePricingRules->update($turf, $pricingRule, $request->pricingRuleAttributes());

        return back();
    }

    public function destroy(Turf $turf, PricingRule $pricingRule): RedirectResponse
    {
        Gate::authorize('update', $turf);
        Gate::authorize('update', $pricingRule->turf);
        abort_unless($pricingRule->turf_id === $turf->id, 404);
        $this->managePricingRules->delete($turf, $pricingRule);

        return back();
    }

    public function quote(QuoteTurfPricingRequest $request, Turf $turf): JsonResponse
    {
        try {
            $quote = $this->pricing->quote($turf, $request->slots());
        } catch (DomainException|InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['slots' => $exception->getMessage()]);
        }

        return response()->json([
            'location_timezone' => $turf->location->timezone,
            'quote' => $quote->toArray(),
        ]);
    }

    /** @return array<string, int|string|bool|null> */
    private function serializeRule(PricingRule $rule): array
    {
        return [
            'id' => $rule->id,
            'rule_type' => $rule->rule_type->value,
            'price_minor' => $rule->price_minor,
            'currency' => $rule->currency,
            'priority' => $rule->priority,
            'effective_from_date' => $rule->effective_from_date,
            'effective_until_date' => $rule->effective_until_date,
            'weekday' => $rule->weekday,
            'special_date' => $rule->special_date,
            'starts_at_time' => $rule->starts_at_time,
            'ends_at_time' => $rule->ends_at_time,
            'ends_next_day' => $rule->ends_next_day,
            'is_active' => $rule->is_active,
        ];
    }
}
