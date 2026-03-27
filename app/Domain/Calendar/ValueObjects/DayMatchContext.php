<?php

namespace App\Domain\Calendar\ValueObjects;

final readonly class DayMatchContext
{
    public function __construct(
        public bool $isHoliday,
        public bool $isBridgeDay,
        public bool $isFirstBridgeDay,
        public ?SeasonBlockRange $seasonBlock,
        public ?float $holidayImpact,
    ) {}
}
