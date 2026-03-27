<?php

namespace App\Actions\Calendar;

use App\Domain\Calendar\Enums\HolidayGroup;
use App\Models\HolidayDefinition;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BuildHolidayDefinitionPayload
{
    private const int DATE_VALIDATION_YEAR = 2024;

    /** @var list<string> Groups whose observed date is defined by a fixed month+day. */
    private const array DATE_BASED_GROUPS = [
        HolidayGroup::Fixed->value,
        HolidayGroup::Emiliani->value,
    ];

    /**
     * @param  array<string, mixed>  $input
     * @return array{
     *     name: string,
     *     en_name: string,
     *     es_name: string,
     *     group: string,
     *     month: ?int,
     *     day: ?int,
     *     easter_offset: ?int,
     *     moves_to_monday: bool,
     *     base_impact_weights: array<int|string, mixed>,
     *     special_overrides: ?array<int|string, mixed>,
     *     sort_order: int,
     *     is_active: bool,
     * }
     */
    public function handle(array $input, ?HolidayDefinition $existing = null): array
    {
        /** @var array{name: string, en_name: string, es_name: string, group: string, month: ?int, day: ?int, easter_offset: ?int, moves_to_monday: bool, base_impact_weights: array<int|string, mixed>, special_overrides: ?array<int|string, mixed>, sort_order: int, is_active: bool} $normalized */
        $normalized = $this->normalize($input);

        Validator::make($normalized, $this->rules($existing))
            ->after(function (ValidatorContract $validator) use ($normalized, $input): void {
                $this->validateGroupConstraints($validator, $normalized);
                $this->validateJsonFields($validator, $input);
            })
            ->validate();

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{
     *     name: string,
     *     en_name: string,
     *     es_name: string,
     *     group: string,
     *     month: ?int,
     *     day: ?int,
     *     easter_offset: ?int,
     *     moves_to_monday: bool,
     *     base_impact_weights: array<int|string, mixed>,
     *     special_overrides: ?array<int|string, mixed>,
     *     sort_order: int,
     *     is_active: bool,
     * }
     */
    private function normalize(array $input): array
    {
        $group = is_string($input['group'] ?? null) ? trim($input['group']) : '';

        return [
            'name' => is_string($input['name'] ?? null) ? Str::lower(trim($input['name'])) : '',
            'en_name' => is_string($input['en_name'] ?? null) ? trim($input['en_name']) : '',
            'es_name' => is_string($input['es_name'] ?? null) ? trim($input['es_name']) : '',
            'group' => $group,
            'month' => $this->nullableIntForGroup($input['month'] ?? null, $group, self::DATE_BASED_GROUPS),
            'day' => $this->nullableIntForGroup($input['day'] ?? null, $group, self::DATE_BASED_GROUPS),
            'easter_offset' => $this->nullableIntForGroup($input['easter_offset'] ?? null, $group, [HolidayGroup::EasterBased->value]),
            'moves_to_monday' => $this->resolveMovesToMonday($input['moves_to_monday'] ?? false, $group),
            'base_impact_weights' => $this->normalizeJsonField($input['base_impact_weights'] ?? null) ?? [],
            'special_overrides' => $this->normalizeJsonField($input['special_overrides'] ?? null),
            'sort_order' => $this->normalizeNullableInt($input['sort_order'] ?? null) ?? 0,
            'is_active' => filter_var($input['is_active'] ?? false, FILTER_VALIDATE_BOOL),
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(?HolidayDefinition $existing): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('holiday_definitions', 'name')->ignore($existing?->id)],
            'en_name' => ['required', 'string', 'max:255'],
            'es_name' => ['required', 'string', 'max:255'],
            'group' => ['required', 'string', Rule::enum(HolidayGroup::class)],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'day' => ['nullable', 'integer', 'between:1,31'],
            'easter_offset' => ['nullable', 'integer', 'between:-100,100'],
            'moves_to_monday' => ['required', 'boolean'],
            'base_impact_weights' => ['required', 'array'],
            'special_overrides' => ['nullable', 'array'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @param  array{group: string, month: ?int, day: ?int, easter_offset: ?int}  $normalized
     */
    private function validateGroupConstraints(ValidatorContract $validator, array $normalized): void
    {
        $group = $normalized['group'];

        if (HolidayGroup::tryFrom($group) === null) {
            return;
        }

        if (in_array($group, self::DATE_BASED_GROUPS, true)) {
            if (! is_int($normalized['month'])) {
                $validator->errors()->add('month', __('validation.required', ['attribute' => __('calendar.settings.holiday_definition_form.fields.month')]));
            }

            if (! is_int($normalized['day'])) {
                $validator->errors()->add('day', __('validation.required', ['attribute' => __('calendar.settings.holiday_definition_form.fields.day')]));
            }

            if ($validator->errors()->isEmpty() && ! checkdate((int) $normalized['month'], (int) $normalized['day'], self::DATE_VALIDATION_YEAR)) {
                $validator->errors()->add('month', __('calendar.settings.validation.invalid_holiday_date'));
            }
        }

        if ($group === HolidayGroup::EasterBased->value && ! is_int($normalized['easter_offset'])) {
            $validator->errors()->add('easter_offset', __('validation.required', ['attribute' => __('calendar.settings.holiday_definition_form.fields.easter_offset')]));
        }
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validateJsonFields(ValidatorContract $validator, array $input): void
    {
        $this->validateJsonField(
            $validator,
            'base_impact_weights',
            $input['base_impact_weights'] ?? null,
            __('calendar.settings.holiday_definition_form.fields.base_impact_weights'),
            required: true,
        );

        $this->validateJsonField(
            $validator,
            'special_overrides',
            $input['special_overrides'] ?? null,
            __('calendar.settings.holiday_definition_form.fields.special_overrides'),
            required: false,
        );
    }

    private function validateJsonField(
        ValidatorContract $validator,
        string $field,
        mixed $value,
        string $attribute,
        bool $required,
    ): void {
        if (is_array($value)) {
            return;
        }

        if ($value === null || $value === '') {
            if ($required) {
                $validator->errors()->add($field, __('validation.required', ['attribute' => $attribute]));
            }

            return;
        }

        if (! is_string($value) || $this->normalizeJsonField($value) === null) {
            $validator->errors()->add($field, __('validation.json', ['attribute' => $attribute]));
        }
    }

    /**
     * @param  list<string>  $allowedGroups
     */
    private function nullableIntForGroup(mixed $value, string $group, array $allowedGroups): ?int
    {
        if (! in_array($group, $allowedGroups, true)) {
            return null;
        }

        return $this->normalizeNullableInt($value);
    }

    private function resolveMovesToMonday(mixed $value, string $group): bool
    {
        return match ($group) {
            HolidayGroup::Fixed->value => false,
            HolidayGroup::Emiliani->value => true,
            default => filter_var($value, FILTER_VALIDATE_BOOL),
        };
    }

    /**
     * @return array<mixed>|null
     */
    private function normalizeJsonField(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    private function normalizeNullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
