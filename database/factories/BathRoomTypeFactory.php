<?php

namespace Database\Factories;

use App\Models\BathRoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BathRoomType>
 */
class BathRoomTypeFactory extends Factory
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
            'name_en' => fake()->words(2, true),
            'name_es' => fake()->words(2, true),
            'description' => fake()->sentence(12),
            'sort_order' => fake()->numberBetween(1, 999),
        ];
    }
}
