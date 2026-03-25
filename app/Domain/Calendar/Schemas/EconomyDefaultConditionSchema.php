<?php

namespace App\Domain\Calendar\Schemas;

class EconomyDefaultConditionSchema extends AbstractPricingRuleConditionSchema
{
    /** {@inheritDoc} */
    public function fields(): array
    {
        return [
            'fallback' => ['type' => 'hidden'],
        ];
    }

    /** {@inheritDoc} */
    public function rules(array $input): array
    {
        return [
            'fallback' => ['nullable', 'boolean'],
        ];
    }

    /** {@inheritDoc} */
    public function normalize(array $input): array
    {
        return ['fallback' => true];
    }

    /** {@inheritDoc} */
    public function summary(array $conditions): string
    {
        return __('calendar.settings.rule_summaries.fallback');
    }
}
