<?php

namespace App\Domain\Calendar\ValueObjects;

use Carbon\CarbonImmutable;

final readonly class SeasonBlockRange
{
    public function __construct(
        public int $blockId,
        public string $name,
        public CarbonImmutable $start,
        public CarbonImmutable $end,
        public int $priority,
    ) {}

    public function contains(CarbonImmutable $date): bool
    {
        return $date->between($this->start, $this->end);
    }
}
