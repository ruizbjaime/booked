<?php

namespace App\Actions\Countries;

use App\Models\Country;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateCountry
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(User $actor, array $input): Country
    {
        Gate::forUser($actor)->authorize('create', Country::class);

        $this->validate($input);

        $isoAlpha2 = is_string($input['iso_alpha2'] ?? null) ? strtoupper($input['iso_alpha2']) : '';
        $isoAlpha3 = is_string($input['iso_alpha3'] ?? null) ? strtoupper($input['iso_alpha3']) : '';

        return Country::create([
            'en_name' => $input['en_name'],
            'es_name' => $input['es_name'],
            'iso_alpha2' => $isoAlpha2,
            'iso_alpha3' => $isoAlpha3,
            'phone_code' => $input['phone_code'],
            'sort_order' => $input['sort_order'],
            'is_active' => (bool) ($input['is_active'] ?? false),
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validate(array $input): void
    {
        Validator::make($input, [
            'en_name' => ['required', 'string', 'max:255'],
            'es_name' => ['required', 'string', 'max:255'],
            'iso_alpha2' => ['required', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/', Rule::unique('countries', 'iso_alpha2')],
            'iso_alpha3' => ['required', 'string', 'size:3', 'regex:/^[A-Za-z]{3}$/', Rule::unique('countries', 'iso_alpha3')],
            'phone_code' => ['required', 'string', 'max:10', 'regex:/^\+?\d{1,10}$/'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ])->validate();
    }
}
