<?php

namespace Database\Factories;

use App\Models\FeeType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeeType>
 */
class FeeTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'en_name' => fake()->unique()->words(2, true),
            'es_name' => fake()->words(2, true),
            'order' => fake()->numberBetween(1, 999),
        ];
    }
}
