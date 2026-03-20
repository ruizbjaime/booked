<?php

namespace App\Actions\Countries;

use App\Models\Country;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateCountry
{
    public function handle(User $actor, Country $country, string $field, mixed $value): void
    {
        Gate::forUser($actor)->authorize('update', $country);

        $normalized = in_array($field, ['iso_alpha2', 'iso_alpha3'], true) && is_string($value)
            ? strtoupper($value)
            : $value;

        $this->validate($country, $field, $normalized);

        $country->update([$field => $normalized]);
    }

    private function validate(Country $country, string $field, mixed $value): void
    {
        $rules = match ($field) {
            'en_name' => ['required', 'string', 'max:255'],
            'es_name' => ['required', 'string', 'max:255'],
            'iso_alpha2' => ['required', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/', Rule::unique('countries', 'iso_alpha2')->ignore($country->id)],
            'iso_alpha3' => ['required', 'string', 'size:3', 'regex:/^[A-Za-z]{3}$/', Rule::unique('countries', 'iso_alpha3')->ignore($country->id)],
            'phone_code' => ['required', 'string', 'max:10', 'regex:/^\+?\d{1,10}$/'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
            default => abort(422),
        };

        Validator::make([$field => $value], [$field => $rules])->validate();
    }
}
