<?php

namespace App\Actions\Calendar;

use App\Models\PricingCategory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BuildPricingCategoryPayload
{
    /**
     * @param  array<string, mixed>  $input
     * @return array{
     *     name: string,
     *     en_name: string,
     *     es_name: string,
     *     level: int,
     *     color: string,
     *     multiplier: float,
     *     sort_order: int,
     *     is_active: bool
     * }
     */
    public function handle(array $input, ?PricingCategory $existingCategory = null): array
    {
        $normalized = $this->normalize($input);

        Validator::make($normalized, $this->rules($existingCategory))->validate();

        if (! is_bool($normalized['is_active'])) { // @codeCoverageIgnore
            throw ValidationException::withMessages([ // @codeCoverageIgnore
                'is_active' => __('validation.boolean', ['attribute' => __('calendar.settings.fields.is_active')]), // @codeCoverageIgnore
            ]); // @codeCoverageIgnore
        }

        $normalized['is_active'] = (bool) $normalized['is_active'];

        return $normalized;
    }

    public function normalizeField(string $field, mixed $value): mixed
    {
        return match ($field) {
            'name' => $this->normalizeLowercaseStringField($value),
            'en_name', 'es_name', 'color' => $this->normalizeTrimmedStringField($value),
            'level', 'sort_order' => $this->normalizeIntegerField($value),
            'multiplier' => $this->normalizeFloatField($value),
            'is_active' => $this->normalizeBoolean($value),
            default => $value,
        };
    }

    public function validateField(PricingCategory $category, string $field, mixed $value): void
    {
        $rules = $this->rules($category);

        abort_unless(array_key_exists($field, $rules), 422);

        Validator::make(
            [$field => $value],
            [$field => $rules[$field]],
        )->validate();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{
     *     name: string,
     *     en_name: string,
     *     es_name: string,
     *     level: int,
     *     color: string,
     *     multiplier: float,
     *     sort_order: int,
     *     is_active: bool|null
     * }
     */
    private function normalize(array $input): array
    {
        return [
            'name' => $this->normalizeLowercaseStringOrEmpty($input['name'] ?? null),
            'en_name' => $this->normalizeTrimmedStringOrEmpty($input['en_name'] ?? null),
            'es_name' => $this->normalizeTrimmedStringOrEmpty($input['es_name'] ?? null),
            'level' => $this->normalizeIntegerOrZero($input['level'] ?? null),
            'color' => $this->normalizeTrimmedStringOrEmpty($input['color'] ?? null),
            'multiplier' => $this->normalizeFloatOrZero($input['multiplier'] ?? null),
            'sort_order' => $this->normalizeIntegerOrZero($input['sort_order'] ?? null),
            'is_active' => array_key_exists('is_active', $input)
                ? $this->normalizeBoolean($input['is_active'])
                : false,
        ];
    }

    private function normalizeLowercaseStringField(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return Str::lower(trim($value));
    }

    private function normalizeTrimmedStringField(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return trim($value);
    }

    private function normalizeIntegerField(mixed $value): mixed
    {
        if (! is_numeric($value)) {
            return $value;
        }

        return (int) $value;
    }

    private function normalizeFloatField(mixed $value): mixed
    {
        if (! is_numeric($value)) {
            return $value;
        }

        return (float) $value;
    }

    private function normalizeLowercaseStringOrEmpty(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        return Str::lower(trim($value));
    }

    private function normalizeTrimmedStringOrEmpty(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        return trim($value);
    }

    private function normalizeIntegerOrZero(mixed $value): int
    {
        if (! is_numeric($value)) {
            return 0;
        }

        return (int) $value;
    }

    private function normalizeFloatOrZero(mixed $value): float
    {
        if (! is_numeric($value)) {
            return 0.0;
        }

        return (float) $value;
    }

    private function normalizeBoolean(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return match ($value) {
                1 => true,
                0 => false,
                default => null,
            };
        }

        if (! is_string($value)) {
            return null;
        }

        return match (Str::lower(trim($value))) {
            '1', 'true', 'on', 'yes' => true,
            '0', 'false', 'off', 'no' => false,
            default => null,
        };
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function rules(?PricingCategory $existingCategory): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('pricing_categories', 'name')->ignore($existingCategory?->id)],
            'en_name' => ['required', 'string', 'max:255'],
            'es_name' => ['required', 'string', 'max:255'],
            'level' => ['required', 'integer', 'min:1', 'max:10', Rule::unique('pricing_categories', 'level')->ignore($existingCategory?->id)],
            'color' => ['required', 'string', 'max:20', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'multiplier' => ['required', 'numeric', 'min:0.01', 'max:99.99'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
