<?php

namespace App\Actions\Calendar;

use App\Models\HolidayDefinition;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateHolidayDefinition
{
    public function handle(User $actor, HolidayDefinition $definition, string $field, mixed $value): void
    {
        Gate::forUser($actor)->authorize('update', $definition);

        $normalized = match ($field) {
            'name' => is_string($value) ? Str::lower(trim($value)) : $value,
            'en_name', 'es_name' => is_string($value) ? trim($value) : $value,
            'base_impact_weights', 'special_overrides' => is_string($value) ? json_decode($value, true) : $value,
            default => $value,
        };

        $this->validate($definition, $field, $normalized);

        $definition->update([$field => $normalized]);
    }

    private function validate(HolidayDefinition $definition, string $field, mixed $value): void
    {
        $rules = match ($field) {
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('holiday_definitions', 'name')->ignore($definition->id)],
            'en_name' => ['required', 'string', 'max:255'],
            'es_name' => ['required', 'string', 'max:255'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'easter_offset' => ['nullable', 'integer', 'min:-100', 'max:100'],
            'moves_to_monday' => ['required', 'boolean'],
            'base_impact_weights' => ['required', 'array'],
            'special_overrides' => ['nullable', 'array'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['required', 'boolean'],
            default => abort(422),
        };

        Validator::make([$field => $value], [$field => $rules])->validate();
    }
}
