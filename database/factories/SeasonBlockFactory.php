<?php

namespace Database\Factories;

use App\Domain\Calendar\Enums\SeasonStrategy;
use App\Models\SeasonBlock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeasonBlock>
 */
class SeasonBlockFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->slug(2),
            'en_name' => fake()->words(3, true),
            'es_name' => fake()->words(3, true),
            'calculation_strategy' => fake()->randomElement(SeasonStrategy::cases()),
            'priority' => fake()->numberBetween(1, 10),
            'sort_order' => fake()->numberBetween(1, 99),
            'is_active' => true,
        ];
    }

    public function fixedRange(int $startMonth, int $startDay, int $endMonth, int $endDay): static
    {
        return $this->state(fn () => [
            'calculation_strategy' => SeasonStrategy::FixedRange,
            'fixed_start_month' => $startMonth,
            'fixed_start_day' => $startDay,
            'fixed_end_month' => $endMonth,
            'fixed_end_day' => $endDay,
        ]);
    }
}
