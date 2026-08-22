<?php

namespace App\Domain\Pricing\Services;

use App\Domain\Pricing\Data\PricedSlotData;
use App\Domain\Pricing\Data\PricingQuoteData;
use App\Domain\Pricing\Enums\PricingRuleType;
use App\Domain\Pricing\Repositories\PricingRuleRepository;
use App\Models\PricingRule;
use App\Models\Turf;
use Carbon\CarbonImmutable;
use DomainException;
use InvalidArgumentException;

final class PricingService
{
    public function __construct(private readonly PricingRuleRepository $pricingRules) {}

    /**
     * @param  list<array{starts_at: CarbonImmutable, ends_at: CarbonImmutable}>  $intervals
     */
    public function quote(Turf $turf, array $intervals): PricingQuoteData
    {
        $turf->loadMissing('location');
        $rules = array_values($this->pricingRules->activeForTurf($turf)->all());
        $slots = [];
        $currency = null;
        $totalMinor = 0;

        foreach ($intervals as $interval) {
            foreach ($this->splitIntoSlots($turf, $interval['starts_at'], $interval['ends_at']) as [$startsAt, $endsAt]) {
                $rule = $this->ruleForSlot($rules, $startsAt, $turf->location->timezone);

                if ($rule === null) {
                    throw new DomainException('No active pricing rule applies to the selected slot.');
                }

                if ($currency !== null && $currency !== $rule->currency) {
                    throw new DomainException('A pricing quote cannot combine currencies.');
                }

                $currency = $rule->currency;
                $totalMinor += $rule->price_minor;
                $slots[] = new PricedSlotData($startsAt, $endsAt, $rule->price_minor, $rule->currency, $rule->id);
            }
        }

        if ($slots === [] || $currency === null) {
            throw new InvalidArgumentException('At least one slot is required for pricing.');
        }

        return new PricingQuoteData($slots, $totalMinor, $currency);
    }

    /**
     * @param  list<PricingRule>  $rules
     */
    private function ruleForSlot(array $rules, CarbonImmutable $startsAt, string $timezone): ?PricingRule
    {
        $localStart = $startsAt->setTimezone($timezone);
        $applicableRules = array_filter(
            $rules,
            fn (PricingRule $rule): bool => $this->ruleApplies($rule, $localStart),
        );

        usort($applicableRules, function (PricingRule $left, PricingRule $right): int {
            $priority = $left->priority <=> $right->priority;

            if ($priority !== 0) {
                return $priority;
            }

            $specificity = $left->rule_type->specificity() <=> $right->rule_type->specificity();

            return $specificity !== 0 ? $specificity : $left->id <=> $right->id;
        });

        return $applicableRules[0] ?? null;
    }

    private function ruleApplies(PricingRule $rule, CarbonImmutable $localStart): bool
    {
        $pricingDate = $this->pricingDate($rule, $localStart);

        if (($rule->effective_from_date !== null && $pricingDate < $rule->effective_from_date)
            || ($rule->effective_until_date !== null && $pricingDate > $rule->effective_until_date)) {
            return false;
        }

        return match ($rule->rule_type) {
            PricingRuleType::Base => true,
            PricingRuleType::Weekday => $localStart->dayOfWeekIso === $rule->weekday,
            PricingRuleType::Weekend => $localStart->dayOfWeekIso >= 6,
            PricingRuleType::PeakHour => $this->matchesPeakHour($rule, $localStart),
            PricingRuleType::SpecialDate => $localStart->format('Y-m-d') === $rule->special_date,
        };
    }

    private function pricingDate(PricingRule $rule, CarbonImmutable $localStart): string
    {
        if ($rule->rule_type === PricingRuleType::PeakHour
            && $rule->ends_next_day
            && $localStart->format('H:i:s') < $rule->ends_at_time) {
            return $localStart->subDay()->format('Y-m-d');
        }

        return $localStart->format('Y-m-d');
    }

    private function matchesPeakHour(PricingRule $rule, CarbonImmutable $localStart): bool
    {
        $time = $localStart->format('H:i:s');

        return $rule->ends_next_day
            ? $time >= $rule->starts_at_time || $time < $rule->ends_at_time
            : $time >= $rule->starts_at_time && $time < $rule->ends_at_time;
    }

    /**
     * @return list<array{0: CarbonImmutable, 1: CarbonImmutable}>
     */
    private function splitIntoSlots(Turf $turf, CarbonImmutable $startsAt, CarbonImmutable $endsAt): array
    {
        if ($endsAt->lessThanOrEqualTo($startsAt)) {
            throw new InvalidArgumentException('The selected slot must end after it starts.');
        }

        $durationSeconds = $startsAt->diffInSeconds($endsAt);
        $slotSeconds = $turf->default_slot_duration_minutes * 60;

        if ($durationSeconds % $slotSeconds !== 0) {
            throw new InvalidArgumentException('The selected duration must be a multiple of the turf slot duration.');
        }

        $slots = [];

        for ($slotStart = $startsAt; $slotStart->lessThan($endsAt); $slotStart = $slotStart->addSeconds($slotSeconds)) {
            $slots[] = [$slotStart, $slotStart->addSeconds($slotSeconds)];
        }

        return $slots;
    }
}
