<?php

namespace App\Actions\Properties;

use App\Models\Property;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateProperty
{
    public function __construct(private GeneratePropertySlug $generatePropertySlug) {}

    public function handle(User $actor, Property $property, string $field, mixed $value): void
    {
        Gate::forUser($actor)->authorize('update', $property);

        $normalized = match ($field) {
            'name', 'city', 'address' => is_string($value) ? trim($value) : $value,
            'base_capacity', 'max_capacity' => blank($value) ? null : $value,
            default => $value,
        };

        $this->validate($property, $field, $normalized);

        if ($field === 'name') {
            abort_unless(is_string($normalized), 422);

            $property->update([
                'name' => $normalized,
                'slug' => $this->generatePropertySlug->handle($normalized, $property),
            ]);

            return;
        }

        $property->update([$field => $normalized]);
    }

    private function validate(Property $property, string $field, mixed $value): void
    {
        $rules = match ($field) {
            'name' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'country_id' => ['required', 'integer', Rule::exists('countries', 'id')->where('is_active', true)],
            'is_active' => ['required', 'boolean'],
            'base_capacity', 'max_capacity' => ['nullable', 'integer', 'min:1', 'max:255'],
            default => abort(422),
        };

        Validator::make([$field => $value], [$field => $rules])
            ->after(function (ValidatorContract $validator) use ($property, $field, $value): void {
                if (! is_int($value)) {
                    return;
                }

                if ($field === 'base_capacity' && $property->max_capacity !== null && $value > $property->max_capacity) {
                    $validator->errors()->add('base_capacity', __('properties.validation.base_capacity_exceeds_max'));
                }

                if ($field === 'max_capacity' && $property->base_capacity !== null && $property->base_capacity > $value) {
                    $validator->errors()->add('max_capacity', __('properties.validation.max_capacity_below_base'));
                }
            })
            ->validate();
    }
}
