<?php

namespace Database\Factories;

use App\Models\PricingCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PricingCategory>
 */
class PricingCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->slug(2),
            'en_name' => fake()->words(2, true),
            'es_name' => fake()->words(2, true),
            'level' => fake()->unique()->numberBetween(1, 10),
            'color' => fake()->hexColor(),
            'multiplier' => fake()->randomFloat(2, 1.00, 3.00),
            'sort_order' => fake()->numberBetween(1, 99),
            'is_active' => true,
        ];
    }
}
