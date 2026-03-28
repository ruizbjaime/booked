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

it('holiday schema exposes validation rules and ignores invalid weekday input types', function () {
    $schema = app(PricingRuleConditionSchemaRegistry::class)->for(PricingRuleType::Holiday);

    expect($schema->rules([]))->toBe([
        'min_impact' => ['nullable', 'integer', 'min:0', 'max:10'],
        'max_impact' => ['nullable', 'integer', 'min:0', 'max:10'],
        'day_of_week' => ['array'],
        'day_of_week.*' => ['string', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
    ])->and($schema->normalize(['day_of_week' => 'friday']))->toBe([]);
});

it('holiday schema can summarize only the base holiday label', function () {
    $schema = app(PricingRuleConditionSchemaRegistry::class)->for(PricingRuleType::Holiday);

    expect($schema->summary([]))->toBe(__('calendar.settings.rule_summaries.holiday_day'));
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

it('exposes the expected fields for each pricing rule schema', function (PricingRuleType $type, array $expectedFields) {
    $schema = app(PricingRuleConditionSchemaRegistry::class)->for($type);

    expect(array_keys($schema->fields()))->toBe($expectedFields);
})->with([
    'season days' => [PricingRuleType::SeasonDays, ['season_mode', 'season_block_id', 'dates', 'days_before', 'days_after', 'day_of_week', 'only_last_n_days', 'exclude_last_n_days']],
    'holiday' => [PricingRuleType::Holiday, ['min_impact', 'max_impact', 'day_of_week']],
    'holiday bridge' => [PricingRuleType::HolidayBridge, ['is_bridge_weekend', 'is_first_bridge_day', 'min_impact', 'max_impact', 'day_of_week']],
    'normal weekend' => [PricingRuleType::NormalWeekend, ['day_of_week', 'outside_season', 'not_bridge']],
    'economy default' => [PricingRuleType::EconomyDefault, ['fallback']],
]);

it('economy default schema always normalizes to fallback true', function () {
    $schema = app(PricingRuleConditionSchemaRegistry::class)->for(PricingRuleType::EconomyDefault);

    expect($schema->normalize(['fallback' => false]))->toBe(['fallback' => true])
        ->and($schema->summary(['fallback' => true]))->toBe(__('calendar.settings.rule_summaries.fallback'));
});

it('normal weekend schema keeps boolean filters and ordered weekdays', function () {
    $schema = app(PricingRuleConditionSchemaRegistry::class)->for(PricingRuleType::NormalWeekend);

    $normalized = $schema->normalize([
        'day_of_week' => ['saturday', 'friday', 'invalid-day'],
        'outside_season' => 'yes',
        'not_bridge' => '1',
    ]);

    expect($normalized)->toBe([
        'day_of_week' => ['friday', 'saturday'],
        'not_bridge' => true,
        'outside_season' => true,
    ]);
});

it('normal weekend schema exposes validation rules and defaults missing booleans to false', function () {
    $schema = app(PricingRuleConditionSchemaRegistry::class)->for(PricingRuleType::NormalWeekend);

    expect($schema->rules([]))->toBe([
        'day_of_week' => ['required', 'array', 'min:1'],
        'day_of_week.*' => ['string', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
        'outside_season' => ['required', 'boolean'],
        'not_bridge' => ['required', 'boolean'],
    ])->and($schema->normalize(['day_of_week' => ['sunday']]))->toBe([
        'day_of_week' => ['sunday'],
        'not_bridge' => false,
        'outside_season' => false,
    ]);
});

it('normal weekend schema can summarize an empty condition set', function () {
    $schema = app(PricingRuleConditionSchemaRegistry::class)->for(PricingRuleType::NormalWeekend);

    expect($schema->summary([]))->toBe('');
});

it('holiday schema includes weekday summaries when provided', function () {
    $schema = app(PricingRuleConditionSchemaRegistry::class)->for(PricingRuleType::Holiday);

    $summary = $schema->summary([
        'min_impact' => 4,
        'max_impact' => 7,
        'day_of_week' => ['friday'],
    ]);

    expect($summary)
        ->toContain(__('calendar.settings.rule_summaries.holiday_day'))
        ->toContain(__('calendar.days_of_week_short.friday'));
});

it('normal weekend schema summary only includes enabled flags', function () {
    $schema = app(PricingRuleConditionSchemaRegistry::class)->for(PricingRuleType::NormalWeekend);

    $summary = $schema->summary([
        'day_of_week' => ['friday'],
        'outside_season' => true,
        'not_bridge' => false,
    ]);

    expect($summary)
        ->toContain(__('calendar.days_of_week_short.friday'))
        ->toContain(__('calendar.settings.rule_summaries.outside_season'))
        ->not->toContain(__('calendar.settings.rule_summaries.exclude_bridge_days'));
});

it('holiday bridge schema includes first day and weekday summaries', function () {
    $schema = app(PricingRuleConditionSchemaRegistry::class)->for(PricingRuleType::HolidayBridge);

    $summary = $schema->summary([
        'is_bridge_weekend' => true,
        'is_first_bridge_day' => true,
        'day_of_week' => ['thursday', 'friday'],
    ]);

    expect($summary)
        ->toContain(__('calendar.settings.rule_summaries.first_bridge_day'))
        ->toContain(__('calendar.days_of_week_short.thursday'));
});

it('season day schema summarizes adjacent recurring dates', function () {
    $schema = app(PricingRuleConditionSchemaRegistry::class)->for(PricingRuleType::SeasonDays);

    $summary = $schema->summary([
        'dates' => ['12-24', '12-31'],
        'days_before' => 2,
        'days_after' => 1,
    ]);

    expect($summary)
        ->toContain('24')
        ->toContain('31')
        ->toContain(__('calendar.settings.rule_summaries.adjacent_days', ['before' => 2, 'after' => 1]));
});

it('season day schema summarizes season blocks using ids and filters', function () {
    $schema = app(PricingRuleConditionSchemaRegistry::class)->for(PricingRuleType::SeasonDays);

    $summary = $schema->summary([
        'season_block_id' => 3,
        'day_of_week' => ['friday'],
        'only_last_n_days' => 2,
        'exclude_last_n_days' => 1,
    ]);

    expect($summary)
        ->toContain(__('calendar.settings.rule_summaries.season_block_id', ['id' => 3]))
        ->toContain(__('calendar.days_of_week_short.friday'))
        ->toContain(__('calendar.settings.rule_summaries.only_last_days', ['count' => 2]))
        ->toContain(__('calendar.settings.rule_summaries.exclude_last_days', ['count' => 1]));
});

it('season day schema keeps positive date adjacency values only', function () {
    $schema = app(PricingRuleConditionSchemaRegistry::class)->for(PricingRuleType::SeasonDays);

    $normalized = $schema->normalize([
        'season_mode' => 'dates',
        'recurring_dates' => ['12-31', 'bad-date', '12-24'],
        'days_before' => '0',
        'days_after' => '2',
    ]);

    expect($normalized)->toBe([
        'dates' => ['12-24', '12-31'],
        'days_after' => 2,
    ]);
});
