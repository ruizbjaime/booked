<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = strtoupper(fake()->unique()->lexify('??'));

        return [
            'en_name' => fake()->country(),
            'es_name' => fake()->country(),
            'iso_alpha2' => $code,
            'iso_alpha3' => strtoupper(fake()->unique()->lexify('???')),
            'phone_code' => '+'.fake()->numberBetween(1, 999),
            'sort_order' => 999,
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
