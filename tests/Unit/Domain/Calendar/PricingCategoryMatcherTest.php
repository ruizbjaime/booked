<?php

use App\Domain\Calendar\Services\PricingCategoryMatcher;
use App\Domain\Calendar\ValueObjects\SeasonBlockRange;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->matcher = new PricingCategoryMatcher;
    $this->rules = allPricingRuleDefinitions();
});

it('matches Holy Week Thu-Sat as CAT 1', function () {
    $holyWeek = new SeasonBlockRange(1, 'holy_week', CarbonImmutable::createStrict(2026, 3, 29), CarbonImmutable::createStrict(2026, 4, 5), 1);

    // Holy Thursday (Apr 2)
    $result = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 4, 2),
        $this->rules,
        isHoliday: true,
        isBridgeDay: false,
        seasonBlock: $holyWeek,
    );

    expect($result)->not->toBeNull()
        ->and($result['pricingCategoryLevel'])->toBe(1);
});

it('matches Holy Week non-premium days as CAT 2', function () {
    $holyWeek = new SeasonBlockRange(1, 'holy_week', CarbonImmutable::createStrict(2026, 3, 29), CarbonImmutable::createStrict(2026, 4, 5), 1);

    // Holy Tuesday (Mar 31)
    $result = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 3, 31),
        $this->rules,
        isHoliday: false,
        isBridgeDay: false,
        seasonBlock: $holyWeek,
    );

    expect($result)->not->toBeNull()
        ->and($result['pricingCategoryLevel'])->toBe(2);
});

it('matches Dec 7-8 as CAT 1 regardless of season', function () {
    $result = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 12, 7),
        $this->rules,
        isHoliday: false,
        isBridgeDay: false,
        seasonBlock: null,
    );

    expect($result)->not->toBeNull()
        ->and($result['pricingCategoryLevel'])->toBe(1);
});

it('matches New Years Eve as CAT 1', function () {
    $result = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 12, 31),
        $this->rules,
        isHoliday: false,
        isBridgeDay: false,
        seasonBlock: null,
    );

    expect($result)->not->toBeNull()
        ->and($result['pricingCategoryLevel'])->toBe(1);
});

it('matches bridge weekend as CAT 2', function () {
    // Bridge Friday before a Monday holiday
    $result = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 7, 17), // Friday before Jul 20 Monday
        $this->rules,
        isHoliday: false,
        isBridgeDay: true,
        seasonBlock: null,
    );

    expect($result)->not->toBeNull()
        ->and($result['pricingCategoryLevel'])->toBe(2);
});

it('matches Foreign Tourist season as CAT 2', function () {
    $foreignTourist = new SeasonBlockRange(4, 'foreign_tourist', CarbonImmutable::createStrict(2026, 1, 15), CarbonImmutable::createStrict(2026, 2, 28), 4);

    $result = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 2, 10), // Tuesday in foreign tourist season
        $this->rules,
        isHoliday: false,
        isBridgeDay: false,
        seasonBlock: $foreignTourist,
    );

    expect($result)->not->toBeNull()
        ->and($result['pricingCategoryLevel'])->toBe(2);
});

it('matches October Recess as CAT 2', function () {
    $octoberRecess = new SeasonBlockRange(3, 'october_recess', CarbonImmutable::createStrict(2026, 10, 10), CarbonImmutable::createStrict(2026, 10, 18), 3);

    $result = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 10, 14), // Wednesday in october recess
        $this->rules,
        isHoliday: false,
        isBridgeDay: false,
        seasonBlock: $octoberRecess,
    );

    expect($result)->not->toBeNull()
        ->and($result['pricingCategoryLevel'])->toBe(2);
});

it('matches normal Fri/Sat outside season as CAT 3', function () {
    // A regular Friday not in any season and not a bridge day
    $result = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 5, 8), // Friday
        $this->rules,
        isHoliday: false,
        isBridgeDay: false,
        seasonBlock: null,
    );

    expect($result)->not->toBeNull()
        ->and($result['pricingCategoryLevel'])->toBe(3);
});

it('does not match normal weekend rule when in season', function () {
    $foreignTourist = new SeasonBlockRange(4, 'foreign_tourist', CarbonImmutable::createStrict(2026, 1, 15), CarbonImmutable::createStrict(2026, 2, 28), 4);

    // Friday inside foreign tourist season should match CAT 2, not CAT 3
    $result = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 1, 23), // Friday
        $this->rules,
        isHoliday: false,
        isBridgeDay: false,
        seasonBlock: $foreignTourist,
    );

    expect($result)->not->toBeNull()
        ->and($result['pricingCategoryLevel'])->toBe(2);
});

it('falls back to CAT 4 economy for unmatched weekdays', function () {
    // A regular Tuesday with no season, no bridge, no holiday
    $result = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 5, 5), // Tuesday
        $this->rules,
        isHoliday: false,
        isBridgeDay: false,
        seasonBlock: null,
    );

    expect($result)->not->toBeNull()
        ->and($result['pricingCategoryLevel'])->toBe(4);
});

it('respects priority order — higher priority rule wins', function () {
    // Holy Week Thursday is both in holy_week season and could be a holiday
    // CAT 1 (priority 1) should win over CAT 2 (priority 13)
    $holyWeek = new SeasonBlockRange(1, 'holy_week', CarbonImmutable::createStrict(2026, 3, 29), CarbonImmutable::createStrict(2026, 4, 5), 1);

    $result = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 4, 2), // Thursday
        $this->rules,
        isHoliday: true,
        isBridgeDay: false,
        seasonBlock: $holyWeek,
    );

    expect($result['pricingCategoryLevel'])->toBe(1);
});
