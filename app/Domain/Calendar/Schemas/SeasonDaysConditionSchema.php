<?php

namespace App\Domain\Calendar\Schemas;

class SeasonDaysConditionSchema extends AbstractPricingRuleConditionSchema
{
    /** {@inheritDoc} */
    public function fields(): array
    {
        return [
            'season_mode' => ['type' => 'select'],
            'season_block_id' => ['type' => 'select'],
            'dates' => ['type' => 'list'],
            'days_before' => ['type' => 'number'],
            'days_after' => ['type' => 'number'],
            'day_of_week' => ['type' => 'checkbox-group'],
            'only_last_n_days' => ['type' => 'number'],
            'exclude_last_n_days' => ['type' => 'number'],
        ];
    }

    /** {@inheritDoc} */
    public function rules(array $input): array
    {
        return [
            'season_mode' => ['required', 'string', 'in:season,dates'],
            'season_block_id' => ['nullable', 'integer', 'exists:season_blocks,id'],
            'day_of_week' => ['array'],
            'day_of_week.*' => ['string', 'in:'.implode(',', self::DAY_ORDER)],
            'only_last_n_days' => ['nullable', 'integer', 'min:1', 'max:31'],
            'exclude_last_n_days' => ['nullable', 'integer', 'min:1', 'max:31'],
            'recurring_dates' => ['array'],
            'recurring_dates.*' => ['string', 'regex:/^\d{2}-\d{2}$/'],
            'days_before' => ['nullable', 'integer', 'min:0', 'max:15'],
            'days_after' => ['nullable', 'integer', 'min:0', 'max:15'],
        ];
    }

    /** {@inheritDoc} */
    public function normalize(array $input): array
    {
        $isDateMode = ($input['season_mode'] ?? 'season') === 'dates';

        if ($isDateMode) {
            $rawDates = $input['recurring_dates'] ?? [];
            $conditions = [
                'dates' => $this->normalizeRecurringDates(is_array($rawDates) ? $rawDates : []),
            ];

            $daysBefore = $this->normalizePositiveInt($input['days_before'] ?? null);
            if ($daysBefore !== null && $daysBefore > 0) {
                $conditions['days_before'] = $daysBefore;
            }

            $daysAfter = $this->normalizePositiveInt($input['days_after'] ?? null);
            if ($daysAfter !== null && $daysAfter > 0) {
                $conditions['days_after'] = $daysAfter;
            }

            return $this->sortConditions($conditions);
        }

        $conditions = [
            'season_block_id' => $this->normalizePositiveInt($input['season_block_id'] ?? null),
        ];

        $days = $this->normalizeDaysOfWeek(is_array($input['day_of_week'] ?? null) ? $input['day_of_week'] : []);
        if ($days !== []) {
            $conditions['day_of_week'] = $days;
        }

        $onlyLastDays = $this->normalizePositiveInt($input['only_last_n_days'] ?? null);
        if ($onlyLastDays !== null) {
            $conditions['only_last_n_days'] = $onlyLastDays;
        }

        $excludeLastDays = $this->normalizePositiveInt($input['exclude_last_n_days'] ?? null);
        if ($excludeLastDays !== null) {
            $conditions['exclude_last_n_days'] = $excludeLastDays;
        }

        return $this->sortConditions(array_filter(
            $conditions,
            fn (mixed $value): bool => $value !== null,
        ));
    }

    /** {@inheritDoc} */
    public function summary(array $conditions): string
    {
        $dates = $conditions['dates'] ?? null;
        if (is_array($dates) && $dates !== []) {
            $summary = __('calendar.settings.rule_summaries.specific_dates', [
                'dates' => implode(', ', array_map(
                    fn (mixed $date): string => $this->humanizeMonthDay(is_string($date) ? $date : ''),
                    $dates,
                )),
            ]);

            $daysBefore = is_int($conditions['days_before'] ?? null) ? $conditions['days_before'] : 0;
            $daysAfter = is_int($conditions['days_after'] ?? null) ? $conditions['days_after'] : 0;

            if ($daysBefore > 0 || $daysAfter > 0) {
                $summary .= ' '.__('calendar.settings.rule_summaries.adjacent_days', [
                    'before' => $daysBefore,
                    'after' => $daysAfter,
                ]);
            }

            return $summary;
        }

        $parts = [
            __('calendar.settings.rule_summaries.season', [
                'season' => isset($conditions['season_block_id']) && is_int($conditions['season_block_id'])
                    ? __('calendar.settings.rule_summaries.season_block_id', ['id' => $conditions['season_block_id']])
                    : (is_string($conditions['season'] ?? null) ? $conditions['season'] : '---'),
            ]),
        ];

        $days = $conditions['day_of_week'] ?? [];
        if (is_array($days) && $days !== []) {
            $parts[] = $this->summarizeDaysOfWeek($days);
        }

        if (isset($conditions['only_last_n_days']) && is_int($conditions['only_last_n_days'])) {
            $parts[] = __('calendar.settings.rule_summaries.only_last_days', [
                'count' => $conditions['only_last_n_days'],
            ]);
        }

        if (isset($conditions['exclude_last_n_days']) && is_int($conditions['exclude_last_n_days'])) {
            $parts[] = __('calendar.settings.rule_summaries.exclude_last_days', [
                'count' => $conditions['exclude_last_n_days'],
            ]);
        }

        return implode(' · ', $parts);
    }
}
