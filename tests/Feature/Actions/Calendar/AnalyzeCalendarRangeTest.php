<?php

use App\Actions\Calendar\AnalyzeCalendarRange;
use App\Domain\Calendar\ValueObjects\DayAnalysis;
use Carbon\CarbonImmutable;
use Database\Seeders\HolidayDefinitionSeeder;
use Database\Seeders\PricingCategorySeeder;
use Database\Seeders\PricingRuleSeeder;
use Database\Seeders\SeasonBlockSeeder;

beforeEach(function () {
    $this->seed([
        HolidayDefinitionSeeder::class,
        SeasonBlockSeeder::class,
        PricingCategorySeeder::class,
        PricingRuleSeeder::class,
    ]);
});

it('returns a DayAnalysis for each day in the range', function () {
    $from = CarbonImmutable::createStrict(2026, 1, 1);
    $to = CarbonImmutable::createStrict(2026, 1, 31);

    $result = app(AnalyzeCalendarRange::class)->handle($from, $to);

    expect($result)->toHaveCount(31)
        ->and($result[0])->toBeInstanceOf(DayAnalysis::class);
});

it('marks holidays with correct metadata', function () {
    $from = CarbonImmutable::createStrict(2026, 1, 1);
    $to = CarbonImmutable::createStrict(2026, 1, 1);

    $result = app(AnalyzeCalendarRange::class)->handle($from, $to);

    $newYear = $result[0];

    expect($newYear->isHoliday)->toBeTrue()
        ->and($newYear->holidayDefinitionId)->not->toBeNull()
        ->and($newYear->holidayGroup)->toBe('fixed')
        ->and($newYear->holidayImpact)->toBeInt()
        ->and($newYear->notes)->toContain('Holiday');
});

it('marks non-holidays correctly', function () {
    // January 2, 2026 is a Friday — not a holiday
    $from = CarbonImmutable::createStrict(2026, 1, 2);
    $to = CarbonImmutable::createStrict(2026, 1, 2);

    $result = app(AnalyzeCalendarRange::class)->handle($from, $to);

    expect($result[0]->isHoliday)->toBeFalse()
        ->and($result[0]->holidayDefinitionId)->toBeNull();
});

it('populates day of week fields', function () {
    // January 5, 2026 is a Monday
    $from = CarbonImmutable::createStrict(2026, 1, 5);
    $to = CarbonImmutable::createStrict(2026, 1, 5);

    $result = app(AnalyzeCalendarRange::class)->handle($from, $to);

    expect($result[0]->dayOfWeek)->toBe(CarbonImmutable::MONDAY)
        ->and($result[0]->dayOfWeekName)->toBe('monday');
});

it('handles a single-day range', function () {
    $date = CarbonImmutable::createStrict(2026, 6, 15);

    $result = app(AnalyzeCalendarRange::class)->handle($date, $date);

    expect($result)->toHaveCount(1)
        ->and($result[0]->date->toDateString())->toBe('2026-06-15');
});

it('handles a multi-year range crossing December into January', function () {
    $from = CarbonImmutable::createStrict(2026, 12, 28);
    $to = CarbonImmutable::createStrict(2027, 1, 3);

    $result = app(AnalyzeCalendarRange::class)->handle($from, $to);

    expect($result)->toHaveCount(7);

    $dates = array_map(fn (DayAnalysis $d) => $d->date->toDateString(), $result);

    expect($dates)->toContain('2026-12-28', '2027-01-01', '2027-01-03');
});

it('returns empty analysis properties when no holidays, seasons, or rules exist', function () {
    // Pass empty pricing rules to avoid DB load; no holidays in mid-February range
    $from = CarbonImmutable::createStrict(2026, 2, 9);
    $to = CarbonImmutable::createStrict(2026, 2, 9);

    $result = app(AnalyzeCalendarRange::class)->handle($from, $to, pricingRules: []);

    expect($result[0]->isHoliday)->toBeFalse()
        ->and($result[0]->isBridgeDay)->toBeFalse()
        ->and($result[0]->pricingCategoryId)->toBeNull()
        ->and($result[0]->matchedPricingRuleId)->toBeNull();
});

it('accepts custom pricing rules instead of loading from database', function () {
    $from = CarbonImmutable::createStrict(2026, 3, 1);
    $to = CarbonImmutable::createStrict(2026, 3, 1);

    $withDbRules = app(AnalyzeCalendarRange::class)->handle($from, $to);
    $withEmptyRules = app(AnalyzeCalendarRange::class)->handle($from, $to, pricingRules: []);

    // With empty rules, no pricing category should be matched
    expect($withEmptyRules[0]->pricingCategoryId)->toBeNull()
        ->and($withEmptyRules[0]->matchedPricingRuleId)->toBeNull();
});

it('detects quincena-adjacent days around the 15th and end of month', function () {
    $from = CarbonImmutable::createStrict(2026, 1, 14);
    $to = CarbonImmutable::createStrict(2026, 1, 16);

    $result = app(AnalyzeCalendarRange::class)->handle($from, $to, pricingRules: []);

    $quincenaFlags = array_map(fn (DayAnalysis $d) => $d->isQuincenaAdjacent, $result);

    // At least one day around the 15th should be quincena-adjacent
    expect(in_array(true, $quincenaFlags, true))->toBeTrue();
});

it('loads pricing rules from database when none are provided', function () {
    $action = app(AnalyzeCalendarRange::class);
    $rules = $action->loadPricingRules();

    expect($rules)->toBeArray()
        ->and($rules)->not->toBeEmpty();
});
