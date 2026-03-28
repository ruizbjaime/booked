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
            $property->update([
                'name' => $value,
                'slug' => $this->generatePropertySlug->handle((string) $value, $property),
            ]);

            return;
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
