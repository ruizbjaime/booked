<?php

namespace App\Domain\Calendar\Services;

use App\Domain\Calendar\Data\PricingRuleData;
use App\Domain\Calendar\Enums\PricingRuleType;
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
    public function match(
        CarbonImmutable $date,
        array $rules,
        bool $isHoliday,
        bool $isBridgeDay,
        bool $isFirstBridgeDay,
        ?SeasonBlockRange $seasonBlock,
    ): ?array {
        $rule = $this->matchRule($date, $rules, $isHoliday, $isBridgeDay, $isFirstBridgeDay, $seasonBlock);

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
    public function matchRule(
        CarbonImmutable $date,
        array $rules,
        bool $isHoliday,
        bool $isBridgeDay,
        bool $isFirstBridgeDay,
        ?SeasonBlockRange $seasonBlock,
    ): ?PricingRuleData {
        $dayName = self::DAY_NAMES[$date->dayOfWeek];
        $monthDay = $date->format('m-d');

        foreach ($rules as $rule) {
            if ($this->matchesRule($rule, $date, $isBridgeDay, $isFirstBridgeDay, $seasonBlock, $dayName, $monthDay)) {
                return $rule;
            }
        }

        return null;
    }

    public function matchesRule(
        PricingRuleData $rule,
        CarbonImmutable $date,
        bool $isBridgeDay,
        bool $isFirstBridgeDay,
        ?SeasonBlockRange $seasonBlock,
        ?string $dayName = null,
        ?string $monthDay = null,
    ): bool {
        $resolvedDayName = $dayName ?? self::DAY_NAMES[$date->dayOfWeek];
        $resolvedMonthDay = $monthDay ?? $date->format('m-d');

        return match ($rule->ruleType) {
            PricingRuleType::SeasonDays => $this->matchSeasonDays($rule, $date, $resolvedDayName, $resolvedMonthDay, $seasonBlock),
            PricingRuleType::HolidayBridge => $this->matchHolidayBridge($rule, $resolvedDayName, $isBridgeDay, $isFirstBridgeDay),
            PricingRuleType::NormalWeekend => $this->matchNormalWeekend($rule, $resolvedDayName, $isBridgeDay, $seasonBlock),
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
            return in_array($monthDay, $conditions['dates'], true);
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

        if (isset($conditions['day_of_week']) && is_array($conditions['day_of_week'])) {
            return in_array($dayName, $conditions['day_of_week'], true);
        }

        return true;
    }

    private function matchHolidayBridge(PricingRuleData $rule, string $dayName, bool $isBridgeDay, bool $isFirstBridgeDay): bool
    {
        $conditions = $rule->conditions;

        if (! empty($conditions['is_bridge_weekend']) && ! $isBridgeDay) {
            return false;
        }

        if (! empty($conditions['is_first_bridge_day']) && ! $isFirstBridgeDay) {
            return false;
        }

        if (isset($conditions['day_of_week']) && is_array($conditions['day_of_week'])) {
            return in_array($dayName, $conditions['day_of_week'], true);
        }

        return $isBridgeDay;
    }

    private function matchNormalWeekend(
        PricingRuleData $rule,
        string $dayName,
        bool $isBridgeDay,
        ?SeasonBlockRange $seasonBlock,
    ): bool {
        $conditions = $rule->conditions;

        if (! empty($conditions['outside_season']) && $seasonBlock !== null) {
            return false;
        }

        if (! empty($conditions['not_bridge']) && $isBridgeDay) {
            return false;
        }

        if (isset($conditions['day_of_week']) && is_array($conditions['day_of_week'])) {
            return in_array($dayName, $conditions['day_of_week'], true);
        }

        return false;
    }

    private function matchEconomyDefault(PricingRuleData $rule): bool
    {
        return ! empty($rule->conditions['fallback']);
    }
}
