<?php

namespace App\Domain\Calendar\Data;

use App\Domain\Calendar\Enums\SeasonStrategy;

final readonly class SeasonBlockData
{
    public function __construct(
        public int $id,
        public string $name,
        public SeasonStrategy $calculationStrategy,
        public ?int $fixedStartMonth = null,
        public ?int $fixedStartDay = null,
        public ?int $fixedEndMonth = null,
        public ?int $fixedEndDay = null,
        public int $priority = 0,
    ) {}
}
