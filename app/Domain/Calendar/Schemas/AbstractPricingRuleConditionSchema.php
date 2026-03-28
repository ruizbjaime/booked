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
    protected const array IMPACT_RULES = ['nullable', 'integer', 'min:0', 'max:10'];

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

    /**
     * @codeCoverageIgnore — currently unused; retained for future schema extensions
     */
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

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $conditions
     * @return array<string, mixed>
     */
    protected function normalizeImpactConditions(array $input, array $conditions): array
    {
        $minImpact = $this->normalizeNullableImpact($input['min_impact'] ?? null);
        $maxImpact = $this->normalizeNullableImpact($input['max_impact'] ?? null);

        if ($minImpact !== null) {
            $conditions['min_impact'] = $minImpact;
        }

        if ($maxImpact !== null) {
            $conditions['max_impact'] = $maxImpact;
        }

        return $conditions;
    }

    /**
     * @param  array<string, mixed>  $conditions
     */
    protected function summarizeImpactRange(array $conditions): ?string
    {
        $minImpact = is_numeric($conditions['min_impact'] ?? null) ? (string) $conditions['min_impact'] : null;
        $maxImpact = is_numeric($conditions['max_impact'] ?? null) ? (string) $conditions['max_impact'] : null;

        if ($minImpact === null && $maxImpact === null) {
            return null;
        }

        return __('calendar.settings.rule_summaries.impact_range', [
            'min' => $minImpact ?? '0',
            'max' => $maxImpact ?? '10',
        ]);
    }

    protected function normalizeNullableImpact(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (int) round((float) $value);
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
                $conditions[$key] = $this->sortConditions($value); // @codeCoverageIgnore — no schema currently produces nested assoc arrays
            }
        }

        ksort($conditions);

        return $conditions;
    }
}
