<?php

namespace App\Domain\Calendar\Schemas;

class HolidayBridgeConditionSchema extends AbstractPricingRuleConditionSchema
{
    /** {@inheritDoc} */
    public function fields(): array
    {
        return [
            'is_bridge_weekend' => ['type' => 'switch'],
            'is_first_bridge_day' => ['type' => 'switch'],
            'min_impact' => ['type' => 'number'],
            'max_impact' => ['type' => 'number'],
            'day_of_week' => ['type' => 'checkbox-group'],
        ];
    }

    /** {@inheritDoc} */
    public function rules(array $input): array
    {
        return [
            'is_bridge_weekend' => ['required', 'boolean'],
            'is_first_bridge_day' => ['required', 'boolean'],
            'min_impact' => self::IMPACT_RULES,
            'max_impact' => self::IMPACT_RULES,
            'day_of_week' => ['array'],
            'day_of_week.*' => ['string', 'in:'.implode(',', self::DAY_ORDER)],
        ];
    }

    /** {@inheritDoc} */
    public function normalize(array $input): array
    {
        $conditions = $this->normalizeImpactConditions($input, [
            'is_bridge_weekend' => $this->normalizeBoolean($input['is_bridge_weekend'] ?? true, true),
            'is_first_bridge_day' => $this->normalizeBoolean($input['is_first_bridge_day'] ?? false),
        ]);

        $days = $this->normalizeDaysOfWeek(is_array($input['day_of_week'] ?? null) ? $input['day_of_week'] : []);

        if ($days !== []) {
            $conditions['day_of_week'] = $days;
        }

        return $this->sortConditions($conditions);
    }

    /** {@inheritDoc} */
    public function summary(array $conditions): string
    {
        $parts = [__('calendar.settings.rule_summaries.bridge_weekend')];

        if (! empty($conditions['is_first_bridge_day'])) {
            $parts[] = __('calendar.settings.rule_summaries.first_bridge_day');
        }

        $impactSummary = $this->summarizeImpactRange($conditions);

        if ($impactSummary !== null) {
            $parts[] = $impactSummary;
        }

        $days = $conditions['day_of_week'] ?? [];
        if (is_array($days) && $days !== []) {
            $parts[] = $this->summarizeDaysOfWeek($days);
        }

        return implode(' · ', $parts);
    }
}
