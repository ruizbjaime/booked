<?php

use App\Domain\Calendar\Data\PricingRuleData;
use App\Domain\Calendar\Enums\PricingRuleType;
use App\Domain\Calendar\Services\PricingCategoryMatcher;
use App\Domain\Calendar\ValueObjects\SeasonBlockRange;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->matcher = new PricingCategoryMatcher;
    $this->rules = allPricingRuleDefinitions();
});

it('matches Holy Week Thu-Sat as CAT 1', function () {
    $holyWeek = new SeasonBlockRange(1, 'holy_week', CarbonImmutable::createStrict(2026, 3, 27), CarbonImmutable::createStrict(2026, 4, 4), 1);

    $result = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 4, 2),
        $this->rules,
        dayContext(isHoliday: true, seasonBlock: $holyWeek, holidayImpact: 10),
    );

    expect($result)->not->toBeNull()
        ->and($result['pricingCategoryLevel'])->toBe(1);
});

it('matches Holy Week non-premium days as CAT 2 including pre-Palm-Sunday', function () {
    $holyWeek = new SeasonBlockRange(1, 'holy_week', CarbonImmutable::createStrict(2026, 3, 27), CarbonImmutable::createStrict(2026, 4, 4), 1);

    $friday = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 3, 27),
        $this->rules,
        dayContext(seasonBlock: $holyWeek),
    );

    $tuesday = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 3, 31),
        $this->rules,
        dayContext(seasonBlock: $holyWeek),
    );

    expect($friday)->not->toBeNull()
        ->and($friday['pricingCategoryLevel'])->toBe(2)
        ->and($tuesday)->not->toBeNull()
        ->and($tuesday['pricingCategoryLevel'])->toBe(2);
});

it('matches Dec 7-8 as CAT 1 regardless of season', function () {
    $result = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 12, 7),
        $this->rules,
        dayContext(),
    );

    expect($result)->not->toBeNull()
        ->and($result['pricingCategoryLevel'])->toBe(1);
});

it('matches New Years Eve as CAT 1', function () {
    $result = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 12, 31),
        $this->rules,
        dayContext(),
    );

    expect($result)->not->toBeNull()
        ->and($result['pricingCategoryLevel'])->toBe(1);
});

it('matches recurring date with days_before', function () {
    $rules = [
        new PricingRuleData(1, 'test', 1, 1, PricingRuleType::SeasonDays, ['dates' => ['12-31'], 'days_before' => 2], 1),
        new PricingRuleData(9, 'fallback', 4, 4, PricingRuleType::EconomyDefault, ['fallback' => true], 100),
    ];

    // Dec 29 = 2 days before Dec 31
    $match = $this->matcher->match(CarbonImmutable::createStrict(2026, 12, 29), $rules, dayContext());
    $noMatch = $this->matcher->match(CarbonImmutable::createStrict(2026, 12, 28), $rules, dayContext());

    expect($match['pricingCategoryLevel'])->toBe(1)
        ->and($noMatch['pricingCategoryLevel'])->toBe(4);
});

it('matches recurring date with days_after across year boundary', function () {
    $rules = [
        new PricingRuleData(1, 'test', 1, 1, PricingRuleType::SeasonDays, ['dates' => ['12-31'], 'days_after' => 1], 1),
        new PricingRuleData(9, 'fallback', 4, 4, PricingRuleType::EconomyDefault, ['fallback' => true], 100),
    ];

    // Jan 1 = 1 day after Dec 31
    $match = $this->matcher->match(CarbonImmutable::createStrict(2027, 1, 1), $rules, dayContext());
    $noMatch = $this->matcher->match(CarbonImmutable::createStrict(2027, 1, 2), $rules, dayContext());

    expect($match['pricingCategoryLevel'])->toBe(1)
        ->and($noMatch['pricingCategoryLevel'])->toBe(4);
});

it('matches recurring date without adjacent days as simple in_array', function () {
    $rules = [
        new PricingRuleData(1, 'test', 1, 1, PricingRuleType::SeasonDays, ['dates' => ['12-31']], 1),
        new PricingRuleData(9, 'fallback', 4, 4, PricingRuleType::EconomyDefault, ['fallback' => true], 100),
    ];

    $match = $this->matcher->match(CarbonImmutable::createStrict(2026, 12, 31), $rules, dayContext());
    $noMatch = $this->matcher->match(CarbonImmutable::createStrict(2026, 12, 30), $rules, dayContext());

    expect($match['pricingCategoryLevel'])->toBe(1)
        ->and($noMatch['pricingCategoryLevel'])->toBe(4);
});

it('matches first bridge day as CAT 2', function () {
    $result = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 7, 17),
        $this->rules,
        dayContext(isBridgeDay: true, isFirstBridgeDay: true, holidayImpact: 10),
    );

    expect($result)->not->toBeNull()
        ->and($result['pricingCategoryLevel'])->toBe(2);
});

it('matches first bridge day on Thursday as CAT 2', function () {
    // Use a Thursday that isn't Dec 24 (which falls in the christmas_eve adjacent range)
    $result = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 4, 30),
        $this->rules,
        dayContext(isBridgeDay: true, isFirstBridgeDay: true, holidayImpact: 10),
    );

    expect($result)->not->toBeNull()
        ->and($result['pricingCategoryLevel'])->toBe(2);
});

it('matches non-first high-impact bridge days as CAT 2', function () {
    $saturday = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 7, 18),
        $this->rules,
        dayContext(isBridgeDay: true, holidayImpact: 10),
    );

    $sunday = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 7, 19),
        $this->rules,
        dayContext(isBridgeDay: true, holidayImpact: 10),
    );

    expect($saturday)->not->toBeNull()
        ->and($saturday['pricingCategoryLevel'])->toBe(2)
        ->and($sunday)->not->toBeNull()
        ->and($sunday['pricingCategoryLevel'])->toBe(2);
});

it('matches non-first low-impact bridge days as CAT 2', function () {
    $result = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 7, 18),
        $this->rules,
        dayContext(isBridgeDay: true, holidayImpact: 4),
    );

    expect($result)->not->toBeNull()
        ->and($result['pricingCategoryLevel'])->toBe(2);
});

it('matches Friday bridge day as CAT 1 when in christmas adjacent range', function () {
    // Dec 25 falls within christmas_eve dates + days_after:3
    $result = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 12, 25),
        $this->rules,
        dayContext(isHoliday: true, isBridgeDay: true, holidayImpact: 10),
    );

    expect($result)->not->toBeNull()
        ->and($result['pricingCategoryLevel'])->toBe(1);
});

it('matches low-impact holiday day as CAT 3', function () {
    // A Friday holiday with impact 4 matches low_impact_holiday rule
    $result = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 8, 7),
        $this->rules,
        dayContext(isHoliday: true, holidayImpact: 4),
    );

    expect($result)->not->toBeNull()
        ->and($result['pricingCategoryLevel'])->toBe(3);
});

it('assigns economy to Monday holidays (checkout day)', function () {
    // Jul 20 (Monday) — Independence Day, checkout day for long weekend
    $result = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 7, 20),
        $this->rules,
        dayContext(isHoliday: true, isCheckoutDay: true, holidayImpact: 10),
    );

    expect($result)->not->toBeNull()
        ->and($result['pricingCategoryLevel'])->toBe(4);
});

it('assigns economy to mid-week holiday checkout days', function () {
    // May 1 2024 is Wednesday — Labor Day, checkout day
    $result = $this->matcher->match(
        CarbonImmutable::createStrict(2024, 5, 1),
        $this->rules,
        dayContext(isHoliday: true, isCheckoutDay: true, holidayImpact: 4),
    );

    expect($result)->not->toBeNull()
        ->and($result['pricingCategoryLevel'])->toBe(4);
});

it('matches holiday eve with impact 4 as CAT 3', function () {
    // Apr 30 2024 (Tuesday) — eve of May 1 (Wednesday holiday, impact 4)
    // Matches low_impact_holiday rule (min_impact: 4, max_impact: 4)
    $result = $this->matcher->match(
        CarbonImmutable::createStrict(2024, 4, 30),
        $this->rules,
        dayContext(isHolidayEve: true, holidayImpact: 4),
    );

    expect($result)->not->toBeNull()
        ->and($result['pricingCategoryLevel'])->toBe(3);
});

it('holiday eve with impact outside rule range falls to economy', function () {
    // Impact 10 does not match low_impact_holiday (min: 4, max: 4)
    $result = $this->matcher->match(
        CarbonImmutable::createStrict(2024, 4, 30),
        $this->rules,
        dayContext(isHolidayEve: true, holidayImpact: 10),
    );

    expect($result)->not->toBeNull()
        ->and($result['pricingCategoryLevel'])->toBe(4);
});

it('matches low-impact holiday with impact 4 as CAT 3', function () {
    $result = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 5, 5),
        $this->rules,
        dayContext(isHoliday: true, holidayImpact: 4),
    );

    expect($result)->not->toBeNull()
        ->and($result['pricingCategoryLevel'])->toBe(3);
});

it('matches October Recess as CAT 3', function () {
    $octoberRecess = new SeasonBlockRange(3, 'october_recess', CarbonImmutable::createStrict(2026, 10, 2), CarbonImmutable::createStrict(2026, 10, 11), 3);

    $result = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 10, 7),
        $this->rules,
        dayContext(seasonBlock: $octoberRecess),
    );

    expect($result)->not->toBeNull()
        ->and($result['pricingCategoryLevel'])->toBe(3);
});

it('matches normal Fri/Sat outside season as CAT 3', function () {
    $result = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 5, 8),
        $this->rules,
        dayContext(),
    );

    expect($result)->not->toBeNull()
        ->and($result['pricingCategoryLevel'])->toBe(3);
});

it('does not match normal weekend rule when in premium holy week days', function () {
    $holyWeek = new SeasonBlockRange(1, 'holy_week', CarbonImmutable::createStrict(2026, 3, 27), CarbonImmutable::createStrict(2026, 4, 4), 1);

    $result = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 4, 3),
        $this->rules,
        dayContext(isHoliday: true, seasonBlock: $holyWeek, holidayImpact: 10),
    );

    expect($result)->not->toBeNull()
        ->and($result['pricingCategoryLevel'])->toBe(1);
});

it('falls back to CAT 4 economy for unmatched weekdays', function () {
    $result = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 5, 5),
        $this->rules,
        dayContext(),
    );

    expect($result)->not->toBeNull()
        ->and($result['pricingCategoryLevel'])->toBe(4);
});

it('respects priority order — higher priority rule wins', function () {
    $result = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 12, 7),
        $this->rules,
        dayContext(),
    );

    expect($result['pricingCategoryLevel'])->toBe(1);
});
