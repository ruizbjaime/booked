<?php

use App\Actions\Calendar\BuildPricingRulePayload;
use App\Models\PricingCategory;
use Database\Seeders\PricingCategorySeeder;
use Database\Seeders\PricingRuleSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed([
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
