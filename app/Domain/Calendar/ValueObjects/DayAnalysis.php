<?php

namespace App\Domain\Calendar\ValueObjects;

use Carbon\CarbonImmutable;

final readonly class DayAnalysis
{
    public function __construct(
        public CarbonImmutable $date,
        public int $dayOfWeek,
        public string $dayOfWeekName,
        public bool $isHoliday,
        public ?int $holidayDefinitionId,
        public ?CarbonImmutable $holidayOriginalDate,
        public ?CarbonImmutable $holidayObservedDate,
        public ?string $holidayGroup,
        public ?float $holidayImpact,
        public bool $isBridgeDay,
        public bool $isFirstBridgeDay,
        public ?int $seasonBlockId,
        public ?string $seasonBlockName,
        public ?int $pricingCategoryId,
        public ?int $pricingCategoryLevel,
        public ?int $matchedPricingRuleId,
        public bool $isQuincenaAdjacent,
        public ?string $notes,
    ) {}
}
