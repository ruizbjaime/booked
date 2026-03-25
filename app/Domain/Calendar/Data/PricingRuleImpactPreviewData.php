<?php

namespace App\Domain\Calendar\Data;

final readonly class PricingRuleImpactPreviewData
{
    /**
     * @param  array<string, int>  $changesByCategory
     * @param  list<PricingRulePreviewSample>  $sampleDates
     * @param  list<string>  $warnings
     */
    public function __construct(
        public int $affectedCount,
        public array $changesByCategory,
        public array $sampleDates,
        public array $warnings,
    ) {}
}
