<?php

namespace App\Domain\Calendar\Data;

use App\Domain\Calendar\Enums\HolidayGroup;

final readonly class HolidayDefinitionData
{
    /**
     * @param  array<string, float>  $baseImpactWeights
     * @param  list<array{location: string, dates: list<string>, impact: float}>|null  $specialOverrides
     */
    public function __construct(
        public int $id,
        public string $name,
        public HolidayGroup $group,
        public ?int $month,
        public ?int $day,
        public ?int $easterOffset,
        public bool $movesToMonday,
        public array $baseImpactWeights,
        public ?array $specialOverrides = null,
    ) {}
}
