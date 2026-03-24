<?php

namespace Database\Factories;

use App\Domain\Calendar\Enums\PricingRuleType;
use App\Models\PricingCategory;
use App\Models\PricingRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PricingRule>
 */
class PricingRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->slug(3),
            'en_description' => fake()->sentence(),
            'es_description' => fake()->sentence(),
            'pricing_category_id' => PricingCategory::factory(),
            'rule_type' => fake()->randomElement(PricingRuleType::cases()),
            'conditions' => ['fallback' => true],
            'priority' => fake()->numberBetween(1, 100),
            'is_active' => true,
        ];
    }
}
