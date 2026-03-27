<?php

namespace App\Domain\Calendar\ValueObjects;

use App\Domain\Calendar\Enums\HolidayGroup;
use Carbon\CarbonImmutable;

final readonly class ResolvedHoliday
{
    public function __construct(
        public int $definitionId,
        public string $name,
        public HolidayGroup $group,
        public CarbonImmutable $originalDate,
        public CarbonImmutable $observedDate,
        public int $impact,
        public bool $wasMoved,
    ) {}
}
