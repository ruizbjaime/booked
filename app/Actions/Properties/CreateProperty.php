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

        $this->validate($input);

        return Property::create($this->propertyData($input));
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validate(array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'country_id' => ['required', 'integer', Rule::exists('countries', 'id')->where('is_active', true)],
            'is_active' => ['required', 'boolean'],
        ])->validate();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function propertyData(array $input): array
    {
        $name = trim((string) $input['name']);

        return [
            'slug' => $this->generatePropertySlug->handle($name),
            'name' => $name,
            'city' => $input['city'],
            'address' => $input['address'],
            'country_id' => $input['country_id'],
            'is_active' => (bool) ($input['is_active'] ?? false),
        ];
    }
}
