<?php

namespace Database\Factories;

use App\Domain\Calendar\Enums\HolidayGroup;
use App\Models\HolidayDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HolidayDefinition>
 */
class HolidayDefinitionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->slug(2),
            'en_name' => fake()->words(3, true),
            'es_name' => fake()->words(3, true),
            'group' => fake()->randomElement(HolidayGroup::cases()),
            'month' => fake()->numberBetween(1, 12),
            'day' => fake()->numberBetween(1, 28),
            'easter_offset' => null,
            'moves_to_monday' => false,
            'base_impact_weights' => ['default' => 9.5],
            'sort_order' => fake()->numberBetween(1, 99),
            'is_active' => true,
        ];
    }

    public function fixed(): static
    {
        return $this->state(fn () => [
            'group' => HolidayGroup::Fixed,
            'easter_offset' => null,
            'moves_to_monday' => false,
        ]);
    }

    public function emiliani(): static
    {
        return $this->state(fn () => [
            'group' => HolidayGroup::Emiliani,
            'easter_offset' => null,
            'moves_to_monday' => true,
        ]);
    }

    public function easterBased(int $offset = 0): static
    {
        return $this->state(fn () => [
            'group' => HolidayGroup::EasterBased,
            'month' => null,
            'day' => null,
            'easter_offset' => $offset,
            'moves_to_monday' => false,
        ]);
    }
}
