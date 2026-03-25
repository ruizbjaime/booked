<?php

namespace App\Domain\Calendar\Schemas;

class NormalWeekendConditionSchema extends AbstractPricingRuleConditionSchema
{
    /** {@inheritDoc} */
    public function fields(): array
    {
        return [
            'day_of_week' => ['type' => 'checkbox-group'],
            'outside_season' => ['type' => 'switch'],
            'not_bridge' => ['type' => 'switch'],
        ];
    }

    /** {@inheritDoc} */
    public function rules(array $input): array
    {
        return [
            'day_of_week' => ['required', 'array', 'min:1'],
            'day_of_week.*' => ['string', 'in:'.implode(',', self::DAY_ORDER)],
            'outside_season' => ['required', 'boolean'],
            'not_bridge' => ['required', 'boolean'],
        ];
    }

    /** {@inheritDoc} */
    public function normalize(array $input): array
    {
        return $this->sortConditions([
            'day_of_week' => $this->normalizeDaysOfWeek(is_array($input['day_of_week'] ?? null) ? $input['day_of_week'] : []),
            'outside_season' => $this->normalizeBoolean($input['outside_season'] ?? false),
            'not_bridge' => $this->normalizeBoolean($input['not_bridge'] ?? false),
        ]);
    }

    /** {@inheritDoc} */
    public function summary(array $conditions): string
    {
        $parts = [];

        $days = $conditions['day_of_week'] ?? [];
        if (is_array($days) && $days !== []) {
            $parts[] = $this->summarizeDaysOfWeek($days);
        }

        if (! empty($conditions['outside_season'])) {
            $parts[] = __('calendar.settings.rule_summaries.outside_season');
        }

        if (! empty($conditions['not_bridge'])) {
            $parts[] = __('calendar.settings.rule_summaries.exclude_bridge_days');
        }

        return implode(' · ', $parts);
    }
}
