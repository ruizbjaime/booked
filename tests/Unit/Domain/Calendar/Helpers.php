<?php

use App\Domain\Calendar\Data\HolidayDefinitionData;
use App\Domain\Calendar\Data\PricingRuleData;
use App\Domain\Calendar\Data\SeasonBlockData;
use App\Domain\Calendar\Enums\HolidayGroup;
use App\Domain\Calendar\Enums\PricingRuleType;
use App\Domain\Calendar\Enums\SeasonStrategy;
use App\Domain\Calendar\ValueObjects\DayMatchContext;
use App\Domain\Calendar\ValueObjects\SeasonBlockRange;

function dayContext(
    bool $isHoliday = false,
    bool $isBridgeDay = false,
    bool $isFirstBridgeDay = false,
    bool $isCheckoutDay = false,
    bool $isHolidayEve = false,
    ?SeasonBlockRange $seasonBlock = null,
    ?int $holidayImpact = null,
): DayMatchContext {
    return new DayMatchContext($isHoliday, $isBridgeDay, $isFirstBridgeDay, $isCheckoutDay, $isHolidayEve, $seasonBlock, $holidayImpact);
}

/**
 * @return list<HolidayDefinitionData>
 */
function allColombianHolidayDefinitions(): array
{
    $fixedWeights = [
        'monday' => 10, 'tuesday' => 7, 'wednesday' => 4,
        'thursday' => 7, 'friday' => 10, 'saturday' => 2, 'sunday' => 2,
    ];

    return [
        // Group A: Fixed
        new HolidayDefinitionData(1, 'new_year', HolidayGroup::Fixed, 1, 1, null, false, $fixedWeights),
        new HolidayDefinitionData(2, 'labor_day', HolidayGroup::Fixed, 5, 1, null, false, $fixedWeights),
        new HolidayDefinitionData(3, 'independence_day', HolidayGroup::Fixed, 7, 20, null, false, $fixedWeights),
        new HolidayDefinitionData(4, 'battle_of_boyaca', HolidayGroup::Fixed, 8, 7, null, false, $fixedWeights),
        new HolidayDefinitionData(5, 'immaculate_conception', HolidayGroup::Fixed, 12, 8, null, false, $fixedWeights, [
            ['location' => 'villa_de_leyva', 'dates' => ['12-07', '12-08'], 'impact' => 10],
        ]),
        new HolidayDefinitionData(6, 'christmas', HolidayGroup::Fixed, 12, 25, null, false, $fixedWeights),

        // Group B: Emiliani
        new HolidayDefinitionData(7, 'epiphany', HolidayGroup::Emiliani, 1, 6, null, true, ['default' => 10]),
        new HolidayDefinitionData(8, 'saint_joseph', HolidayGroup::Emiliani, 3, 19, null, true, ['default' => 10]),
        new HolidayDefinitionData(9, 'saints_peter_and_paul', HolidayGroup::Emiliani, 6, 29, null, true, ['default' => 10]),
        new HolidayDefinitionData(10, 'assumption_of_mary', HolidayGroup::Emiliani, 8, 15, null, true, ['default' => 10]),
        new HolidayDefinitionData(11, 'columbus_day', HolidayGroup::Emiliani, 10, 12, null, true, ['default' => 10]),
        new HolidayDefinitionData(12, 'all_saints', HolidayGroup::Emiliani, 11, 1, null, true, ['default' => 10]),
        new HolidayDefinitionData(13, 'independence_of_cartagena', HolidayGroup::Emiliani, 11, 11, null, true, ['default' => 10]),

        // Group C: Easter-based
        new HolidayDefinitionData(14, 'holy_thursday', HolidayGroup::EasterBased, null, null, -3, false, ['default' => 10]),
        new HolidayDefinitionData(15, 'good_friday', HolidayGroup::EasterBased, null, null, -2, false, ['default' => 10]),
        new HolidayDefinitionData(16, 'ascension', HolidayGroup::EasterBased, null, null, 39, true, ['default' => 10]),
        new HolidayDefinitionData(17, 'corpus_christi', HolidayGroup::EasterBased, null, null, 60, true, ['default' => 10]),
        new HolidayDefinitionData(18, 'sacred_heart', HolidayGroup::EasterBased, null, null, 68, true, ['default' => 10]),
    ];
}

/**
 * @return list<SeasonBlockData>
 */
function allSeasonBlockDefinitions(): array
{
    return [
        new SeasonBlockData(1, 'holy_week', SeasonStrategy::HolyWeek, priority: 1),
        new SeasonBlockData(2, 'december_season', SeasonStrategy::DecemberSeason, priority: 2),
        new SeasonBlockData(3, 'october_recess', SeasonStrategy::OctoberRecess, priority: 3),
    ];
}

/**
 * @return list<PricingRuleData>
 */
function allPricingRuleDefinitions(): array
{
    return [
        new PricingRuleData(1, 'holy_week', 1, 1, PricingRuleType::SeasonDays, ['season_block_id' => 1, 'only_last_n_days' => 3], 1),
        new PricingRuleData(2, 'dec_7_8_villa', 1, 1, PricingRuleType::SeasonDays, ['dates' => ['12-07', '12-08']], 2),
        new PricingRuleData(3, 'christmas_eve_new_years_eve', 1, 1, PricingRuleType::SeasonDays, ['dates' => ['12-24', '12-31'], 'days_before' => 3, 'days_after' => 3], 3),
        new PricingRuleData(8, 'holy_week_non_premium', 2, 2, PricingRuleType::SeasonDays, ['season_block_id' => 1, 'exclude_last_n_days' => 3], 4),
        new PricingRuleData(12, 'december_season', 2, 2, PricingRuleType::SeasonDays, ['season_block_id' => 2], 5),
        new PricingRuleData(5, 'bridge_first_day', 2, 2, PricingRuleType::HolidayBridge, ['is_bridge_weekend' => true, 'is_first_bridge_day' => true], 6),
        new PricingRuleData(6, 'long_weekend_high_impact', 2, 2, PricingRuleType::HolidayBridge, ['is_bridge_weekend' => true, 'day_of_week' => ['thursday', 'friday', 'saturday', 'sunday'], 'min_impact' => 8], 7),
        new PricingRuleData(7, 'long_weekend_low_impact', 2, 2, PricingRuleType::HolidayBridge, ['day_of_week' => ['thursday', 'friday', 'saturday', 'sunday'], 'is_bridge_weekend' => true, 'is_first_bridge_day' => false, 'max_impact' => 7], 8),
        new PricingRuleData(4, 'low_impact_holiday', 3, 3, PricingRuleType::Holiday, ['min_impact' => 4, 'max_impact' => 4], 9),
        new PricingRuleData(9, 'october_recess', 3, 3, PricingRuleType::SeasonDays, ['season_block_id' => 3], 10),
        new PricingRuleData(10, 'normal_weekend', 3, 3, PricingRuleType::NormalWeekend, ['day_of_week' => ['friday', 'saturday'], 'outside_season' => true, 'not_bridge' => true], 11),
        new PricingRuleData(11, 'economy_fallback', 4, 4, PricingRuleType::EconomyDefault, ['fallback' => true], 999),
    ];
}
