<?php

namespace Database\Factories;

use App\Models\BedType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BedType>
 */
class BedTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->slug(2),
            'en_name' => fake()->words(2, true),
            'es_name' => fake()->words(2, true),
            'bed_capacity' => fake()->numberBetween(1, 6),
            'sort_order' => fake()->numberBetween(1, 999),
        ];
    }
}
