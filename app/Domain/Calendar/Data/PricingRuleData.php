<?php

namespace App\Domain\Calendar\Data;

use App\Domain\Calendar\Enums\PricingRuleType;

final readonly class PricingRuleData
{
    /**
     * @param  array<string, mixed>  $conditions
     */
    public function __construct(
        public int $id,
        public string $name,
        public int $pricingCategoryId,
        public int $pricingCategoryLevel,
        public PricingRuleType $ruleType,
        public array $conditions,
        public int $priority,
    ) {}
}
