<?php

namespace App\Domain\Calendar\Services;

use App\Domain\Calendar\Data\PricingRuleData;
use App\Domain\Calendar\Enums\PricingRuleType;
use App\Domain\Calendar\ValueObjects\DayMatchContext;
use App\Domain\Calendar\ValueObjects\SeasonBlockRange;
use Carbon\CarbonImmutable;

final class PricingCategoryMatcher
{
    private const array DAY_NAMES = [
        0 => 'sunday',
        1 => 'monday',
        2 => 'tuesday',
        3 => 'wednesday',
        4 => 'thursday',
        5 => 'friday',
        6 => 'saturday',
    ];

    /**
     * Match the first applicable pricing rule and return its category info.
     *
     * @param  list<PricingRuleData>  $rules  Sorted by priority ascending
     * @return array{pricingCategoryId: int, pricingCategoryLevel: int}|null
     */
    public function match(CarbonImmutable $date, array $rules, DayMatchContext $context): ?array
    {
        $rule = $this->matchRule($date, $rules, $context);

        if ($rule === null) {
            return null;
        }

        return [
            'pricingCategoryId' => $rule->pricingCategoryId,
            'pricingCategoryLevel' => $rule->pricingCategoryLevel,
        ];
    }

    /**
     * Match the first applicable pricing rule and return the full rule data.
     *
     * @param  list<PricingRuleData>  $rules  Sorted by priority ascending
     */
    public function matchRule(CarbonImmutable $date, array $rules, DayMatchContext $context): ?PricingRuleData
    {
        $dayName = self::DAY_NAMES[$date->dayOfWeek];
        $monthDay = $date->format('m-d');

        foreach ($rules as $rule) {
            if ($this->matchesRule($rule, $date, $context, $dayName, $monthDay)) {
                return $rule;
            }
        }

        return null;
    }

    public function matchesRule(
        PricingRuleData $rule,
        CarbonImmutable $date,
        DayMatchContext $context,
        ?string $dayName = null,
        ?string $monthDay = null,
    ): bool {
        $resolvedDayName = $dayName ?? self::DAY_NAMES[$date->dayOfWeek];
        $resolvedMonthDay = $monthDay ?? $date->format('m-d');

        return match ($rule->ruleType) {
            PricingRuleType::SeasonDays => $this->matchSeasonDays($rule, $date, $resolvedDayName, $resolvedMonthDay, $context->seasonBlock),
            PricingRuleType::Holiday => $this->matchHoliday($rule, $resolvedDayName, $context),
            PricingRuleType::HolidayBridge => $this->matchHolidayBridge($rule, $resolvedDayName, $context),
            PricingRuleType::NormalWeekend => $this->matchNormalWeekend($rule, $resolvedDayName, $context),
            PricingRuleType::EconomyDefault => $this->matchEconomyDefault($rule),
        };
    }

    private function matchSeasonDays(
        PricingRuleData $rule,
        CarbonImmutable $date,
        string $dayName,
        string $monthDay,
        ?SeasonBlockRange $seasonBlock,
    ): bool {
        $conditions = $rule->conditions;

        if (isset($conditions['dates']) && is_array($conditions['dates'])) {
            return $this->matchesRecurringDates($date, $monthDay, $conditions);
        }

        $expectedSeasonBlockId = $conditions['season_block_id'] ?? null;

        if (is_int($expectedSeasonBlockId)) {
            if ($seasonBlock === null || $seasonBlock->blockId !== $expectedSeasonBlockId) {
                return false;
            }
        } elseif (isset($conditions['season'])) {
            if (! is_string($conditions['season'])) {
                return false;
            }

            if ($seasonBlock === null || $seasonBlock->name !== $conditions['season']) {
                return false;
            }
        } else {
            return false;
        }

        $onlyLastDays = $conditions['only_last_n_days'] ?? null;
        if (is_int($onlyLastDays) && $onlyLastDays > 0 && $date->lt($seasonBlock->end->subDays($onlyLastDays - 1))) {
            return false;
        }

        $excludeLastDays = $conditions['exclude_last_n_days'] ?? null;
        if (is_int($excludeLastDays) && $excludeLastDays > 0 && $date->gte($seasonBlock->end->subDays($excludeLastDays - 1))) {
            return false;
        }

        return $this->matchesDayOfWeek($conditions, $dayName) ?? true;
    }

    private function matchHoliday(PricingRuleData $rule, string $dayName, DayMatchContext $context): bool
    {
        if (! $context->isHoliday && ! $context->isHolidayEve) {
            return false;
        }

        if ($context->isCheckoutDay) {
            return false;
        }

        $conditions = $rule->conditions;

        if (! $this->matchesImpactThresholds($conditions, $context->holidayImpact)) {
            return false;
        }

        return $this->matchesDayOfWeek($conditions, $dayName) ?? true;
    }

    private function matchHolidayBridge(PricingRuleData $rule, string $dayName, DayMatchContext $context): bool
    {
        $conditions = $rule->conditions;

        if (! empty($conditions['is_bridge_weekend']) && ! $context->isBridgeDay) {
            return false;
        }

        if (! empty($conditions['is_first_bridge_day']) && ! $context->isFirstBridgeDay) {
            return false;
        }

        if (! $this->matchesImpactThresholds($conditions, $context->holidayImpact)) {
            return false;
        }

        return $this->matchesDayOfWeek($conditions, $dayName) ?? $context->isBridgeDay;
    }

    private function matchNormalWeekend(PricingRuleData $rule, string $dayName, DayMatchContext $context): bool
    {
        $conditions = $rule->conditions;

        if (! empty($conditions['outside_season']) && $context->seasonBlock !== null) {
            return false;
        }

        if (! empty($conditions['not_bridge']) && $context->isBridgeDay) {
            return false;
        }

        return $this->matchesDayOfWeek($conditions, $dayName) ?? false;
    }

    private function matchEconomyDefault(PricingRuleData $rule): bool
    {
        return ! empty($rule->conditions['fallback']);
    }

    /**
     * @param  array<string, mixed>  $conditions
     */
    private function matchesRecurringDates(CarbonImmutable $date, string $monthDay, array $conditions): bool
    {
        /** @var list<string> $dates */
        $dates = $conditions['dates'];
        $daysBefore = is_int($conditions['days_before'] ?? null) ? $conditions['days_before'] : 0;
        $daysAfter = is_int($conditions['days_after'] ?? null) ? $conditions['days_after'] : 0;

        if ($daysBefore === 0 && $daysAfter === 0) {
            return in_array($monthDay, $dates, true);
        }

        foreach ($dates as $md) {
            $month = substr($md, 0, 2);
            $day = substr($md, 3, 2);

            foreach ([$date->year - 1, $date->year, $date->year + 1] as $year) {
                $anchor = CarbonImmutable::createFromFormat('!Y-m-d', $year.'-'.$month.'-'.$day);

                if (! $anchor instanceof CarbonImmutable) {
                    continue;
                }

                if ($date->between($anchor->subDays($daysBefore), $anchor->addDays($daysAfter))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $conditions
     */
    private function matchesDayOfWeek(array $conditions, string $dayName): ?bool
    {
        if (isset($conditions['day_of_week']) && is_array($conditions['day_of_week'])) {
            return in_array($dayName, $conditions['day_of_week'], true);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $conditions
     */
    private function matchesImpactThresholds(array $conditions, ?int $holidayImpact): bool
    {
        $minImpact = $conditions['min_impact'] ?? null;

        if (is_numeric($minImpact) && ($holidayImpact === null || $holidayImpact < (int) $minImpact)) {
            return false;
        }

        $maxImpact = $conditions['max_impact'] ?? null;

        if (is_numeric($maxImpact) && ($holidayImpact === null || $holidayImpact > (int) $maxImpact)) {
            return false;
        }

        return true;
    }
}
