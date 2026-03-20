<?php

namespace Database\Factories;

use App\Models\IdentificationDocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IdentificationDocumentType>
 */
class IdentificationDocumentTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'en_name' => fake()->words(2, true),
            'es_name' => fake()->words(2, true),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
