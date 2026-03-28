<?php

namespace App\Actions\Properties;

use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateProperty
{
    public function __construct(private GeneratePropertySlug $generatePropertySlug) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(User $actor, array $input): Property
    {
        Gate::forUser($actor)->authorize('create', Property::class);

        $validated = $this->validate($input);

        return Property::create([
            'slug' => $this->generatePropertySlug->handle($validated['name']),
            'name' => $validated['name'],
            'city' => $validated['city'],
            'address' => $validated['address'],
            'country_id' => $validated['country_id'],
            'is_active' => $validated['is_active'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{name: string, city: string, address: string, country_id: int, is_active: bool}
     */
    private function validate(array $input): array
    {
        $validated = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'country_id' => ['required', 'integer', Rule::exists('countries', 'id')->where('is_active', true)],
            'is_active' => ['required', 'boolean'],
        ])->validate();

        $name = $validated['name'];
        $city = $validated['city'];
        $address = $validated['address'];
        $countryId = $validated['country_id'];
        $isActive = $validated['is_active'];

        abort_unless(is_string($name) && is_string($city) && is_string($address), 422);
        abort_unless(is_int($countryId), 422);
        abort_unless(is_bool($isActive), 422);

        return [
            'name' => trim($name),
            'city' => trim($city),
            'address' => trim($address),
            'country_id' => $countryId,
            'is_active' => $isActive,
        ];
    }
}
