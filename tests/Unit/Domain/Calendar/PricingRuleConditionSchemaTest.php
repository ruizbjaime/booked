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
