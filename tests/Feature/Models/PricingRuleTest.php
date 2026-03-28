<?php

use App\Domain\Calendar\Enums\PricingRuleType;
use App\Models\PricingCategory;
use App\Models\PricingRule;

it('casts pricing rule attributes and filters active rules', function () {
    PricingRule::factory()->create(['is_active' => true]);
    PricingRule::factory()->create(['is_active' => false]);

    $rule = PricingRule::factory()->create([
        'rule_type' => PricingRuleType::Holiday,
        'conditions' => ['min_impact' => 5],
        'priority' => 12,
        'is_active' => true,
    ])->fresh();

    expect(PricingRule::query()->active()->count())->toBe(2)
        ->and($rule->rule_type)->toBe(PricingRuleType::Holiday)
        ->and($rule->conditions)->toBe(['min_impact' => 5])
        ->and($rule->priority)->toBe(12)
        ->and($rule->is_active)->toBeTrue()
        ->and($rule->is_active)->toBeBool();
});

it('returns localized description and accessor for both locales', function () {
    $rule = PricingRule::factory()->create([
        'en_description' => 'Holiday uplift',
        'es_description' => 'Aumento festivo',
    ]);

    app()->setLocale('en');

    expect($rule->localizedDescription())->toBe('Holiday uplift')
        ->and($rule->localized_description_attribute)->toBe('Holiday uplift');

    app()->setLocale('es');

    expect($rule->localizedDescription())->toBe('Aumento festivo')
        ->and($rule->localized_description_attribute)->toBe('Aumento festivo');
});

it('belongs to a pricing category', function () {
    $category = PricingCategory::factory()->create();
    $rule = PricingRule::factory()->for($category, 'pricingCategory')->create();

    expect($rule->pricingCategory->is($category))->toBeTrue();
});
