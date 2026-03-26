<?php

namespace App\Actions\Calendar;

use App\Domain\Calendar\Enums\SeasonStrategy;
use App\Models\SeasonBlock;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BuildSeasonBlockPayload
{
    private const int FIXED_RANGE_VALIDATION_YEAR = 2026;

    /**
     * @var list<string>
     */
    private const array FIXED_RANGE_DATE_FIELDS = [
        'fixed_start_month',
        'fixed_start_day',
        'fixed_end_month',
        'fixed_end_day',
    ];

    /**
     * @param  array<string, mixed>  $input
     * @return array{
     *     name: string,
     *     en_name: string,
     *     es_name: string,
     *     calculation_strategy: string,
     *     fixed_start_month: ?int,
     *     fixed_start_day: ?int,
     *     fixed_end_month: ?int,
     *     fixed_end_day: ?int,
     *     priority: int,
     *     sort_order: int,
     *     is_active: bool
     * }
     */
    public function handle(array $input, ?SeasonBlock $existingBlock = null): array
    {
        $normalized = $this->normalize($input);

        Validator::make($normalized, $this->rules($existingBlock))
            ->after(fn (ValidatorContract $validator) => $this->validateFixedRange($validator, $normalized))
            ->validate();

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{
     *     name: string,
     *     en_name: string,
     *     es_name: string,
     *     calculation_strategy: string,
     *     fixed_start_month: ?int,
     *     fixed_start_day: ?int,
     *     fixed_end_month: ?int,
     *     fixed_end_day: ?int,
     *     priority: int,
     *     sort_order: int,
     *     is_active: bool
     * }
     */
    private function normalize(array $input): array
    {
        return [
            'name' => is_string($input['name'] ?? null) ? Str::lower(trim($input['name'])) : '',
            'en_name' => is_string($input['en_name'] ?? null) ? trim($input['en_name']) : '',
            'es_name' => is_string($input['es_name'] ?? null) ? trim($input['es_name']) : '',
            'calculation_strategy' => is_string($input['calculation_strategy'] ?? null) ? trim($input['calculation_strategy']) : '',
            'fixed_start_month' => $this->normalizeNullableInt($input['fixed_start_month'] ?? null),
            'fixed_start_day' => $this->normalizeNullableInt($input['fixed_start_day'] ?? null),
            'fixed_end_month' => $this->normalizeNullableInt($input['fixed_end_month'] ?? null),
            'fixed_end_day' => $this->normalizeNullableInt($input['fixed_end_day'] ?? null),
            'priority' => $this->normalizeNullableInt($input['priority'] ?? null) ?? 0,
            'sort_order' => $this->normalizeNullableInt($input['sort_order'] ?? null) ?? 0,
            'is_active' => filter_var($input['is_active'] ?? false, FILTER_VALIDATE_BOOL),
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(?SeasonBlock $existingBlock): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('season_blocks', 'name')->ignore($existingBlock?->id)],
            'en_name' => ['required', 'string', 'max:255'],
            'es_name' => ['required', 'string', 'max:255'],
            'calculation_strategy' => ['required', 'string', Rule::enum(SeasonStrategy::class)],
            'fixed_start_month' => ['nullable', 'integer', 'between:1,12'],
            'fixed_start_day' => ['nullable', 'integer', 'between:1,31'],
            'fixed_end_month' => ['nullable', 'integer', 'between:1,12'],
            'fixed_end_day' => ['nullable', 'integer', 'between:1,31'],
            'priority' => ['required', 'integer', 'min:0', 'max:9999'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @param  array<string, mixed>  $normalized
     */
    private function validateFixedRange(ValidatorContract $validator, array $normalized): void
    {
        if (($normalized['calculation_strategy'] ?? null) !== SeasonStrategy::FixedRange->value) {
            return;
        }

        foreach (self::FIXED_RANGE_DATE_FIELDS as $field) {
            if (! is_int($normalized[$field] ?? null)) {
                $validator->errors()->add($field, __('calendar.settings.validation.fixed_range_dates_required'));
            }
        }

        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        /** @var int $startMonth */
        $startMonth = $normalized['fixed_start_month'];
        /** @var int $startDay */
        $startDay = $normalized['fixed_start_day'];
        /** @var int $endMonth */
        $endMonth = $normalized['fixed_end_month'];
        /** @var int $endDay */
        $endDay = $normalized['fixed_end_day'];

        if (! checkdate($startMonth, $startDay, self::FIXED_RANGE_VALIDATION_YEAR)
            || ! checkdate($endMonth, $endDay, self::FIXED_RANGE_VALIDATION_YEAR)) {
            $validator->errors()->add('fixed_start_month', __('calendar.settings.validation.fixed_range_invalid_date'));

            return;
        }

        $start = CarbonImmutable::createStrict(self::FIXED_RANGE_VALIDATION_YEAR, $startMonth, $startDay);
        $end = CarbonImmutable::createStrict(self::FIXED_RANGE_VALIDATION_YEAR, $endMonth, $endDay);

        if ($end->lt($start)) {
            $validator->errors()->add('fixed_end_month', __('calendar.settings.validation.fixed_range_end_before_start'));
        }
    }

    private function normalizeNullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
