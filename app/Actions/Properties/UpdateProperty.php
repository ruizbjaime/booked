<?php

namespace App\Actions\Properties;

use App\Models\Country;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateProperty
{
    public function __construct(private GeneratePropertySlug $generatePropertySlug) {}

    public function handle(User $actor, Property $property, string $field, mixed $value): void
    {
        Gate::forUser($actor)->authorize('update', $property);

        $this->validate($field, $value);

        if ($field === 'name') {
            abort_unless(is_string($value), 422);

            $property->update([
                'name' => $value,
                'slug' => $this->generatePropertySlug->handle($value, $property),
            ]);

            return;
        }

        if (in_array($field, ['city', 'address'], true)) {
            abort_unless(is_string($value), 422);

            $property->update([$field => $value]);

            return;
        }

        if ($field === 'country_id') {
            if (is_string($value) && ctype_digit($value)) {
                $value = (int) $value;
            }

            abort_unless(is_int($value), 422);

            $property->update([$field => $value]);

            return;
        }

        if (! is_bool($value)) {
            $normalizedBoolean = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

            abort_unless($normalizedBoolean !== null, 422);

            $value = $normalizedBoolean;
        }

        $property->update([$field => $value]);
    }

    private function validate(string $field, mixed $value): void
    {
        $rules = match ($field) {
            'name' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'country_id' => ['required', 'integer', Rule::exists(Country::class, 'id')->where('is_active', true)],
            'is_active' => ['required', 'boolean'],
            default => abort(422),
        };

        Validator::make([$field => $value], [$field => $rules])->validate();
    }
}
