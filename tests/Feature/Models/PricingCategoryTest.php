<?php

use App\Models\CalendarDay;
use App\Models\PricingCategory;
use App\Models\PricingRule;
use Carbon\CarbonImmutable;

it('returns localized name, localized column, and accessor for both locales', function () {
    $category = PricingCategory::factory()->create([
        'en_name' => 'Premium',
        'es_name' => 'Premium ES',
    ]);

    app()->setLocale('en');

    expect($category->localizedName())->toBe('Premium')
        ->and(PricingCategory::localizedNameColumn())->toBe('en_name')
        ->and($category->localized_name_attribute)->toBe('Premium');

    app()->setLocale('es');

    expect($category->localizedName())->toBe('Premium ES')
        ->and(PricingCategory::localizedNameColumn())->toBe('es_name')
        ->and($category->localized_name_attribute)->toBe('Premium ES');
});

it('filters active categories and casts multiplier and is_active', function () {
    PricingCategory::factory()->create(['is_active' => true]);
    PricingCategory::factory()->create(['is_active' => false]);

    $category = PricingCategory::factory()->create([
        'multiplier' => 2.5,
        'is_active' => true,
    ])->fresh();

    expect(PricingCategory::query()->active()->count())->toBe(2)
        ->and($category->multiplier)->toBe('2.50')
        ->and($category->is_active)->toBeTrue()
        ->and($category->is_active)->toBeBool();
});

it('exposes pricing rules and calendar days relationships', function () {
    $category = PricingCategory::factory()->create();
    $rule = PricingRule::factory()->for($category, 'pricingCategory')->create();
    $day = CalendarDay::factory()->forDate(CarbonImmutable::parse('2026-03-10'))->create([
        'pricing_category_id' => $category->id,
        'pricing_category_level' => $category->level,
    ]);

    expect($category->pricingRules)->toHaveCount(1)
        ->and($category->pricingRules->first()->is($rule))->toBeTrue()
        ->and($category->calendarDays)->toHaveCount(1)
        ->and($category->calendarDays->first()->is($day))->toBeTrue();
});
