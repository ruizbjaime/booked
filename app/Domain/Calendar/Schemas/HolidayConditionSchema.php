<?php

namespace App\Domain\Calendar\Schemas;

class HolidayConditionSchema extends AbstractPricingRuleConditionSchema
{
    /** {@inheritDoc} */
    public function fields(): array
    {
        return [
            'min_impact' => ['type' => 'number'],
            'max_impact' => ['type' => 'number'],
            'day_of_week' => ['type' => 'checkbox-group'],
        ];
    }

    /** {@inheritDoc} */
    public function rules(array $input): array
    {
        return [
            'min_impact' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'max_impact' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'day_of_week' => ['array'],
            'day_of_week.*' => ['string', 'in:'.implode(',', self::DAY_ORDER)],
        ];
    }

    /** {@inheritDoc} */
    public function normalize(array $input): array
    {
        $conditions = [];

        $minImpact = $this->normalizeNullableFloat($input['min_impact'] ?? null);
        $maxImpact = $this->normalizeNullableFloat($input['max_impact'] ?? null);

        if ($minImpact !== null) {
            $conditions['min_impact'] = $minImpact;
        }

        if ($maxImpact !== null) {
            $conditions['max_impact'] = $maxImpact;
        }

        $days = $this->normalizeDaysOfWeek(is_array($input['day_of_week'] ?? null) ? $input['day_of_week'] : []);

        if ($days !== []) {
            $conditions['day_of_week'] = $days;
        }

        return $this->sortConditions($conditions);
    }

    /** {@inheritDoc} */
    public function summary(array $conditions): string
    {
        $parts = [__('calendar.settings.rule_summaries.holiday_day')];

        $minImpact = is_numeric($conditions['min_impact'] ?? null) ? (string) $conditions['min_impact'] : null;
        $maxImpact = is_numeric($conditions['max_impact'] ?? null) ? (string) $conditions['max_impact'] : null;

        if ($minImpact !== null || $maxImpact !== null) {
            $parts[] = __('calendar.settings.rule_summaries.impact_range', [
                'min' => $minImpact ?? '0',
                'max' => $maxImpact ?? '10',
            ]);
        }

        $days = $conditions['day_of_week'] ?? [];
        if (is_array($days) && $days !== []) {
            $parts[] = $this->summarizeDaysOfWeek($days);
        }

        return implode(' · ', $parts);
    }
}
