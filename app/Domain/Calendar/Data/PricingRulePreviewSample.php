<?php

namespace App\Domain\Calendar\Data;

use Carbon\CarbonImmutable;

final readonly class PricingRulePreviewSample
{
    public function __construct(
        public CarbonImmutable $date,
        public string $fromCategory,
        public string $toCategory,
    ) {}
}
