<?php

namespace Database\Factories;

use App\Models\ChargeBasis;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChargeBasis>
 */
class ChargeBasisFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => str_replace('-', '_', fake()->unique()->slug(2)),
            'en_name' => fake()->words(2, true),
            'es_name' => fake()->words(2, true),
            'en_description' => fake()->sentence(),
            'es_description' => fake()->sentence(),
            'order' => fake()->numberBetween(1, 999),
            'is_active' => true,
            'metadata' => [
                'requires_quantity' => false,
                'quantity_subject' => null,
            ],
        ];
    }
}
