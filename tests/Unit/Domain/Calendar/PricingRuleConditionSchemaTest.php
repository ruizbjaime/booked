<?php

use App\Domain\Calendar\Enums\PricingRuleType;
use App\Domain\Calendar\PricingRuleConditionSchemaRegistry;
use Tests\TestCase;

uses(TestCase::class);

it('normalizes season day recurring dates with stable ordering', function () {
    $schema = app(PricingRuleConditionSchemaRegistry::class)->for(PricingRuleType::SeasonDays);

    $normalized = $schema->normalize([
        'season_mode' => 'dates',
        'recurring_dates' => ['12-31', '01-01', '12-31'],
    ]);

    expect($normalized)->toBe([
        'dates' => ['01-01', '12-31'],
    ]);
});

it('normalizes season block conditions with stable ids', function () {
    $schema = app(PricingRuleConditionSchemaRegistry::class)->for(PricingRuleType::SeasonDays);

    $normalized = $schema->normalize([
        'season_mode' => 'season',
        'season_block_id' => 7,
        'day_of_week' => ['saturday', 'friday'],
    ]);

    expect($normalized)->toBe([
        'day_of_week' => ['friday', 'saturday'],
        'season_block_id' => 7,
    ]);
});

it('builds a readable summary for normal weekend rules', function () {
    $schema = app(PricingRuleConditionSchemaRegistry::class)->for(PricingRuleType::NormalWeekend);

    $summary = $schema->summary([
        'day_of_week' => ['friday', 'saturday'],
        'outside_season' => true,
        'not_bridge' => true,
    ]);

    expect($summary)
        ->toContain(__('calendar.days_of_week_short.friday'))
        ->toContain(__('calendar.settings.rule_summaries.outside_season'))
        ->toContain(__('calendar.settings.rule_summaries.exclude_bridge_days'));
});

it('normalizes holiday bridge conditions with impact thresholds', function () {
    $schema = app(PricingRuleConditionSchemaRegistry::class)->for(PricingRuleType::HolidayBridge);

    $normalized = $schema->normalize([
        'is_bridge_weekend' => true,
        'is_first_bridge_day' => false,
        'min_impact' => '8',
        'max_impact' => null,
        'day_of_week' => ['friday', 'saturday'],
    ]);

    expect($normalized)->toBe([
        'day_of_week' => ['friday', 'saturday'],
        'is_bridge_weekend' => true,
        'is_first_bridge_day' => false,
        'min_impact' => 8,
    ]);
});

it('normalizes holiday conditions with impact range and days', function () {
    $schema = app(PricingRuleConditionSchemaRegistry::class)->for(PricingRuleType::Holiday);

    $normalized = $schema->normalize([
        'min_impact' => '6',
        'max_impact' => '9',
        'day_of_week' => ['monday'],
    ]);

    expect($normalized)->toBe([
        'day_of_week' => ['monday'],
        'max_impact' => 9,
        'min_impact' => 6,
    ]);
});

it('normalizes holiday conditions with empty impact as omitted', function () {
    $schema = app(PricingRuleConditionSchemaRegistry::class)->for(PricingRuleType::Holiday);

    $normalized = $schema->normalize([
        'min_impact' => '',
        'max_impact' => '',
        'day_of_week' => [],
    ]);

    expect($normalized)->toBe([]);
});

it('builds a readable summary for holiday rules with impact range', function () {
    $schema = app(PricingRuleConditionSchemaRegistry::class)->for(PricingRuleType::Holiday);

    $summary = $schema->summary([
        'min_impact' => 8,
        'max_impact' => 10,
    ]);

    expect($summary)
        ->toContain(__('calendar.settings.rule_summaries.holiday_day'))
        ->toContain('8');
});

it('builds a readable summary for holiday bridge rules with impact range', function () {
    $schema = app(PricingRuleConditionSchemaRegistry::class)->for(PricingRuleType::HolidayBridge);

    $summary = $schema->summary([
        'is_bridge_weekend' => true,
        'is_first_bridge_day' => false,
        'min_impact' => 8,
    ]);

    expect($summary)
        ->toContain(__('calendar.settings.rule_summaries.bridge_weekend'))
        ->toContain('8');
});
