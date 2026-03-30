<?php

namespace Database\Factories;

use App\Models\Bedroom;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bedroom>
 */
class BedroomFactory extends Factory
{
    protected $model = Bedroom::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Bedroom '.fake()->unique()->numberBetween(1, 999);

        return [
            'property_id' => Property::factory(),
            'en_name' => $name,
            'es_name' => 'Habitación '.fake()->unique()->numberBetween(1, 999),
            'en_description' => fake()->sentence(),
            'es_description' => fake()->sentence(),
        ];
    }
}
