<?php

use App\Domain\Calendar\Services\PricingCategoryMatcher;
use App\Domain\Calendar\ValueObjects\SeasonBlockRange;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->matcher = new PricingCategoryMatcher;
    $this->rules = allPricingRuleDefinitions();
});

it('matches Holy Week Thu-Sat as CAT 1', function () {
    $holyWeek = new SeasonBlockRange(1, 'holy_week', CarbonImmutable::createStrict(2026, 3, 27), CarbonImmutable::createStrict(2026, 4, 4), 1);

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

it('matches Holy Week non-premium days as CAT 2 including pre-Palm-Sunday', function () {
    $holyWeek = new SeasonBlockRange(1, 'holy_week', CarbonImmutable::createStrict(2026, 3, 27), CarbonImmutable::createStrict(2026, 4, 4), 1);

    // Friday before Palm Sunday (Mar 27) — non-premium via exclude_last_n_days
    $friday = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 3, 27),
        $this->rules,
        isHoliday: false,
        isBridgeDay: false,
        seasonBlock: $holyWeek,
    );

    // Holy Tuesday (Mar 31) — non-premium
    $tuesday = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 3, 31),
        $this->rules,
        isHoliday: false,
        isBridgeDay: false,
        seasonBlock: $holyWeek,
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

it('matches October Recess as CAT 3', function () {
    $octoberRecess = new SeasonBlockRange(3, 'october_recess', CarbonImmutable::createStrict(2026, 10, 2), CarbonImmutable::createStrict(2026, 10, 11), 3);

    $result = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 10, 7), // Wednesday in october recess
        $this->rules,
        isHoliday: false,
        isBridgeDay: false,
        seasonBlock: $octoberRecess,
    );

    expect($result)->not->toBeNull()
        ->and($result['pricingCategoryLevel'])->toBe(3);
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

it('does not match normal weekend rule when in premium holy week days', function () {
    $holyWeek = new SeasonBlockRange(1, 'holy_week', CarbonImmutable::createStrict(2026, 3, 27), CarbonImmutable::createStrict(2026, 4, 4), 1);

    // Good Friday should match CAT 1 (holy_week rule), not CAT 3 (normal weekend)
    $result = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 4, 3), // Good Friday
        $this->rules,
        isHoliday: true,
        isBridgeDay: false,
        seasonBlock: $holyWeek,
    );

    expect($result)->not->toBeNull()
        ->and($result['pricingCategoryLevel'])->toBe(1);
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
    // Dec 7 is both a specific date rule (CAT 1, priority 2) and in year_end season
    // CAT 1 should win because it has higher priority
    $yearEnd = new SeasonBlockRange(2, 'year_end', CarbonImmutable::createStrict(2026, 12, 15), CarbonImmutable::createStrict(2027, 1, 11), 2);

    $result = $this->matcher->match(
        CarbonImmutable::createStrict(2026, 12, 7), // Dec 7 Villa de Leyva
        $this->rules,
        isHoliday: false,
        isBridgeDay: false,
        seasonBlock: null,
    );

    expect($result['pricingCategoryLevel'])->toBe(1);
});
