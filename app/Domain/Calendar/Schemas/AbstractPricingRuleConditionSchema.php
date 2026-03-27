<?php

namespace App\Domain\Calendar\Schemas;

use App\Domain\Calendar\Contracts\PricingRuleConditionSchema;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;

abstract class AbstractPricingRuleConditionSchema implements PricingRuleConditionSchema
{
    /**
     * @var list<string>
     */
    protected const array DAY_ORDER = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ];

    /**
     * @param  array<int|string, mixed>|null  $days
     * @return list<string>
     */
    protected function normalizeDaysOfWeek(?array $days): array
    {
        $lowered = array_map(
            fn (mixed $day): ?string => is_string($day) ? mb_strtolower(trim($day)) : null,
            $days ?? [],
        );

        return array_values(array_intersect(self::DAY_ORDER, $lowered));
    }

    protected function normalizeNullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    protected function normalizePositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = filter_var($value, FILTER_VALIDATE_INT);

        if (! is_int($normalized) || $normalized <= 0) {
            return null;
        }

        return $normalized;
    }

    protected function normalizeBoolean(mixed $value, bool $default = false): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    /**
     * @param  array<int|string, mixed>  $dates
     * @return list<string>
     */
    protected function normalizeRecurringDates(array $dates): array
    {
        $normalized = array_values(array_unique(array_filter(
            array_map(fn (mixed $date): ?string => is_string($date) && preg_match('/^\d{2}-\d{2}$/', trim($date)) === 1
                ? trim($date)
                : null,
                $dates,
            ),
        )));

        sort($normalized);

        return $normalized;
    }

    /**
     * @param  non-empty-array<mixed, mixed>  $days
     */
    protected function summarizeDaysOfWeek(array $days): string
    {
        return __('calendar.settings.rule_summaries.days', [
            'days' => implode(', ', array_map(
                fn (mixed $day): string => __('calendar.days_of_week_short.'.(is_string($day) ? $day : '')),
                $days,
            )),
        ]);
    }

    protected function normalizeNullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return round((float) $value, 1);
    }

    protected function humanizeMonthDay(string $monthDay): string
    {
        [$month, $day] = array_map('intval', explode('-', $monthDay));

        return CarbonImmutable::createStrict(2000, $month, $day)->translatedFormat('M d');
    }

    /**
     * @param  array<string, mixed>  $conditions
     * @return array<string, mixed>
     */
    protected function sortConditions(array $conditions): array
    {
        foreach ($conditions as $key => $value) {
            if (is_array($value) && Arr::isAssoc($value)) {
                /** @var array<string, mixed> $value */
                $conditions[$key] = $this->sortConditions($value);
            }
        }

        ksort($conditions);

        return $conditions;
    }
}
