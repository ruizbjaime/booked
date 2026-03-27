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
        $conditions = $this->normalizeImpactConditions($input, []);

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
