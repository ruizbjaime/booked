<?php

namespace App\Domain\Calendar;

use App\Domain\Calendar\Contracts\PricingRuleConditionSchema;
use App\Domain\Calendar\Enums\PricingRuleType;
use App\Domain\Calendar\Schemas\EconomyDefaultConditionSchema;
use App\Domain\Calendar\Schemas\HolidayBridgeConditionSchema;
use App\Domain\Calendar\Schemas\HolidayConditionSchema;
use App\Domain\Calendar\Schemas\NormalWeekendConditionSchema;
use App\Domain\Calendar\Schemas\SeasonDaysConditionSchema;

final class PricingRuleConditionSchemaRegistry
{
    public function __construct(
        private readonly SeasonDaysConditionSchema $seasonDays = new SeasonDaysConditionSchema,
        private readonly HolidayConditionSchema $holiday = new HolidayConditionSchema,
        private readonly HolidayBridgeConditionSchema $holidayBridge = new HolidayBridgeConditionSchema,
        private readonly NormalWeekendConditionSchema $normalWeekend = new NormalWeekendConditionSchema,
        private readonly EconomyDefaultConditionSchema $economyDefault = new EconomyDefaultConditionSchema,
    ) {}

    public function for(PricingRuleType|string $type): PricingRuleConditionSchema
    {
        $resolved = $type instanceof PricingRuleType ? $type : PricingRuleType::from($type);

        return match ($resolved) {
            PricingRuleType::SeasonDays => $this->seasonDays,
            PricingRuleType::Holiday => $this->holiday,
            PricingRuleType::HolidayBridge => $this->holidayBridge,
            PricingRuleType::NormalWeekend => $this->normalWeekend,
            PricingRuleType::EconomyDefault => $this->economyDefault,
        };
    }
}
