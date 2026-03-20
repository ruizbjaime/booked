<?php

namespace App\Actions\Platforms;

use App\Models\Platform;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdatePlatform
{
    public function handle(User $actor, Platform $platform, string $field, mixed $value): void
    {
        Gate::forUser($actor)->authorize('update', $platform);

        $this->validate($platform, $field, $value);

        $stored = in_array($field, ['commission', 'commission_tax'], true) && is_numeric($value)
            ? (float) $value / 100
            : $value;

        $platform->update([$field => $stored]);
    }

    private function validate(Platform $platform, string $field, mixed $value): void
    {
        $rules = match ($field) {
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-z][a-z0-9_-]*$/', Rule::unique('platforms', 'name')->ignore($platform->id)],
            'en_name' => ['required', 'string', 'max:255', Rule::unique('platforms', 'en_name')->ignore($platform->id)],
            'es_name' => ['required', 'string', 'max:255', Rule::unique('platforms', 'es_name')->ignore($platform->id)],
            'color' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! is_string($value) || ! CreatePlatform::isValidColor($value)) {
                    $fail(__('validation.in', ['attribute' => $attribute]));
                }
            }],
            'sort_order' => ['required', 'integer', 'min:0'],
            'commission' => ['required', 'numeric', 'min:0', 'max:100'],
            'commission_tax' => ['required', 'numeric', 'min:0', 'max:100'],
            default => abort(422),
        };

        Validator::make([$field => $value], [$field => $rules])->validate();
    }
}
