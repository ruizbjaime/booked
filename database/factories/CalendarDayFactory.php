<?php

namespace Database\Factories;

use App\Models\CalendarDay;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalendarDay>
 */
class CalendarDayFactory extends Factory
{
    public function definition(): array
    {
        $date = CarbonImmutable::instance(fake()->dateTimeBetween('-1 year', '+1 year'));

        return [
            'date' => $date->toDateString(),
            'year' => $date->year,
            'month' => $date->month,
            'day_of_week' => $date->dayOfWeek,
            'day_of_week_name' => strtolower($date->format('l')),
            'is_holiday' => false,
            'is_bridge_day' => false,
            'is_quincena_adjacent' => false,
        ];
    }

    public function forDate(CarbonImmutable $date): static
    {
        return $this->state(fn () => [
            'date' => $date->toDateString(),
            'year' => $date->year,
            'month' => $date->month,
            'day_of_week' => $date->dayOfWeek,
            'day_of_week_name' => strtolower($date->format('l')),
        ]);
    }

    public function holiday(): static
    {
        return $this->state(fn () => [
            'is_holiday' => true,
        ]);
    }
}
