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

        $name = $validated['name'] ?? null;
        $city = $validated['city'] ?? null;
        $address = $validated['address'] ?? null;
        $countryId = $validated['country_id'] ?? null;
        $isActive = $validated['is_active'] ?? null;

        abort_unless(is_string($name), 422);
        abort_unless(is_string($city), 422);
        abort_unless(is_string($address), 422);

        if (is_string($countryId) && ctype_digit($countryId)) {
            $countryId = (int) $countryId;
        }

        abort_unless(is_int($countryId), 422);

        if (! is_bool($isActive)) {
            $normalizedBoolean = filter_var($isActive, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

            abort_unless($normalizedBoolean !== null, 422);

            $isActive = $normalizedBoolean;
        }

        return [
            'name' => trim($name),
            'city' => trim($city),
            'address' => trim($address),
            'country_id' => $countryId,
            'is_active' => $isActive,
        ];
    }
}
