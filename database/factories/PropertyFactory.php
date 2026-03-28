<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    protected $model = Property::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $label = 'Property '.fake()->unique()->city();

        return [
            'slug' => (string) Str::of($label)
                ->lower()
                ->ascii()
                ->replaceMatches('/[^a-z0-9\s_-]+/', '')
                ->replaceMatches('/\s+/', '_')
                ->replaceMatches('/_+/', '_')
                ->trim('_'),
            'name' => $label,
            'city' => fake()->city(),
            'address' => fake()->streetAddress(),
            'country_id' => Country::factory(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
