<?php

namespace App\Actions\Properties;

use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CreateProperty
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(User $actor, array $input): Property
    {
        Gate::forUser($actor)->authorize('create', Property::class);

        $this->validate($input);

        $name = trim((string) $input['name']);

        return Property::create([
            'slug' => $this->generateUniqueSlug($name),
            'name' => $name,
            'city' => $input['city'],
            'address' => $input['address'],
            'country_id' => $input['country_id'],
            'is_active' => (bool) ($input['is_active'] ?? false),
        ]);
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

    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::of($name)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9\s_-]+/', '')
            ->replaceMatches('/\s+/', '_')
            ->replaceMatches('/_+/', '_')
            ->trim('_')
            ->value();

        $slug = $baseSlug !== '' ? $baseSlug : 'property';

        if (! Property::query()->where('slug', $slug)->exists()) {
            return $slug;
        }

        do {
            $candidate = $slug.'_'.$this->randomAlphaSuffix(4);
        } while (Property::query()->where('slug', $candidate)->exists());

        return $candidate;
    }

    private function randomAlphaSuffix(int $length): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyz';
        $suffix = '';

        for ($index = 0; $index < $length; $index++) {
            $suffix .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $suffix;
    }
}
