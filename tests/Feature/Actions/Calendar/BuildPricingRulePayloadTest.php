<?php

use App\Actions\Calendar\BuildPricingRulePayload;
use App\Models\PricingCategory;
use App\Models\SeasonBlock;
use Database\Seeders\PricingCategorySeeder;
use Database\Seeders\PricingRuleSeeder;
use Database\Seeders\SeasonBlockSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed([
        SeasonBlockSeeder::class,
        PricingCategorySeeder::class,
        PricingRuleSeeder::class,
    ]);
});

it('rejects duplicate active priorities', function () {
    expect(fn () => app(BuildPricingRulePayload::class)->handle([
        'name' => 'competing_rule',
        'en_description' => 'Competing rule',
        'es_description' => 'Regla competidora',
        'pricing_category_id' => PricingCategory::query()->where('name', 'cat_2_high')->value('id'),
        'rule_type' => 'season_days',
        'priority' => 1,
        'is_active' => true,
        'season_mode' => 'dates',
        'recurring_dates' => ['11-01'],
    ]))->toThrow(ValidationException::class);
});

it('rejects an active fallback that is not last', function () {
    expect(fn () => app(BuildPricingRulePayload::class)->handle([
        'name' => 'new_fallback',
        'en_description' => 'Fallback',
        'es_description' => 'Fallback',
        'pricing_category_id' => PricingCategory::query()->where('name', 'cat_4_economy')->value('id'),
        'rule_type' => 'economy_default',
        'priority' => 10,
        'is_active' => true,
    ]))->toThrow(ValidationException::class);
});

it('normalizes season block rules using stable season block ids', function () {
    $payload = app(BuildPricingRulePayload::class)->handle([
        'name' => 'mid_year_high',
        'en_description' => 'Mid-year high season',
        'es_description' => 'Temporada alta de mitad de año',
        'pricing_category_id' => PricingCategory::query()->where('name', 'cat_2_high')->value('id'),
        'rule_type' => 'season_days',
        'priority' => 15,
        'is_active' => true,
        'season_mode' => 'season',
        'season_block_id' => SeasonBlock::query()->where('name', 'october_recess')->value('id'),
        'day_of_week' => ['friday', 'saturday'],
    ]);

    expect($payload['conditions'])->toBe([
        'day_of_week' => ['friday', 'saturday'],
        'season_block_id' => SeasonBlock::query()->where('name', 'october_recess')->value('id'),
    ]);
});
