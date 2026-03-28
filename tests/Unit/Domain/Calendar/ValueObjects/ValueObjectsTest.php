<?php

use App\Domain\Calendar\Enums\HolidayGroup;
use App\Domain\Calendar\ValueObjects\BridgeDayInfo;
use App\Domain\Calendar\ValueObjects\DayAnalysis;
use App\Domain\Calendar\ValueObjects\DayMatchContext;
use App\Domain\Calendar\ValueObjects\ResolvedHoliday;
use App\Domain\Calendar\ValueObjects\SeasonBlockRange;
use Carbon\CarbonImmutable;

// --- BridgeDayInfo ---

it('constructs a BridgeDayInfo with correct properties', function () {
    $info = new BridgeDayInfo(definitionId: 5, impact: 3);

    expect($info->definitionId)->toBe(5)
        ->and($info->impact)->toBe(3);
});

// --- ResolvedHoliday ---

it('constructs a ResolvedHoliday with all properties', function () {
    $original = CarbonImmutable::createStrict(2026, 1, 1);
    $observed = CarbonImmutable::createStrict(2026, 1, 5);

    $holiday = new ResolvedHoliday(
        definitionId: 1,
        name: 'New Year',
        group: HolidayGroup::Fixed,
        originalDate: $original,
        observedDate: $observed,
        impact: 5,
        wasMoved: true,
    );

    expect($holiday->definitionId)->toBe(1)
        ->and($holiday->name)->toBe('New Year')
        ->and($holiday->group)->toBe(HolidayGroup::Fixed)
        ->and($holiday->originalDate->toDateString())->toBe('2026-01-01')
        ->and($holiday->observedDate->toDateString())->toBe('2026-01-05')
        ->and($holiday->impact)->toBe(5)
        ->and($holiday->wasMoved)->toBeTrue();
});

// --- SeasonBlockRange ---

it('constructs a SeasonBlockRange and checks containment', function () {
    $range = new SeasonBlockRange(
        blockId: 1,
        name: 'Holy Week',
        start: CarbonImmutable::createStrict(2026, 3, 29),
        end: CarbonImmutable::createStrict(2026, 4, 5),
        priority: 1,
    );

    expect($range->contains(CarbonImmutable::createStrict(2026, 4, 1)))->toBeTrue()
        ->and($range->contains(CarbonImmutable::createStrict(2026, 3, 29)))->toBeTrue()
        ->and($range->contains(CarbonImmutable::createStrict(2026, 4, 5)))->toBeTrue()
        ->and($range->contains(CarbonImmutable::createStrict(2026, 4, 6)))->toBeFalse()
        ->and($range->contains(CarbonImmutable::createStrict(2026, 3, 28)))->toBeFalse();
});

// --- DayMatchContext ---

it('constructs a DayMatchContext with all flags', function () {
    $range = new SeasonBlockRange(
        blockId: 1,
        name: 'December',
        start: CarbonImmutable::createStrict(2026, 12, 1),
        end: CarbonImmutable::createStrict(2026, 12, 31),
        priority: 1,
    );

    $context = new DayMatchContext(
        isHoliday: true,
        isBridgeDay: false,
        isFirstBridgeDay: false,
        isCheckoutDay: true,
        isHolidayEve: false,
        seasonBlock: $range,
        holidayImpact: 4,
    );

    expect($context->isHoliday)->toBeTrue()
        ->and($context->isBridgeDay)->toBeFalse()
        ->and($context->isCheckoutDay)->toBeTrue()
        ->and($context->seasonBlock)->toBe($range)
        ->and($context->holidayImpact)->toBe(4);
});

it('constructs a DayMatchContext with null season block', function () {
    $context = new DayMatchContext(
        isHoliday: false,
        isBridgeDay: false,
        isFirstBridgeDay: false,
        isCheckoutDay: false,
        isHolidayEve: false,
        seasonBlock: null,
        holidayImpact: null,
    );

    expect($context->seasonBlock)->toBeNull()
        ->and($context->holidayImpact)->toBeNull();
});

// --- DayAnalysis ---

it('constructs a DayAnalysis with holiday data', function () {
    $date = CarbonImmutable::createStrict(2026, 1, 1);

    $analysis = new DayAnalysis(
        date: $date,
        dayOfWeek: $date->dayOfWeek,
        dayOfWeekName: 'thursday',
        isHoliday: true,
        holidayDefinitionId: 1,
        holidayOriginalDate: $date,
        holidayObservedDate: $date,
        holidayGroup: 'fixed',
        holidayImpact: 5,
        isBridgeDay: false,
        isFirstBridgeDay: false,
        seasonBlockId: null,
        seasonBlockName: null,
        pricingCategoryId: 2,
        pricingCategoryLevel: 3,
        matchedPricingRuleId: 10,
        isQuincenaAdjacent: false,
        notes: 'Holiday: New Year',
    );

    expect($analysis->isHoliday)->toBeTrue()
        ->and($analysis->holidayGroup)->toBe('fixed')
        ->and($analysis->pricingCategoryId)->toBe(2)
        ->and($analysis->matchedPricingRuleId)->toBe(10)
        ->and($analysis->notes)->toBe('Holiday: New Year');
});

it('constructs a DayAnalysis with null optional fields', function () {
    $date = CarbonImmutable::createStrict(2026, 2, 10);

    $analysis = new DayAnalysis(
        date: $date,
        dayOfWeek: $date->dayOfWeek,
        dayOfWeekName: 'tuesday',
        isHoliday: false,
        holidayDefinitionId: null,
        holidayOriginalDate: null,
        holidayObservedDate: null,
        holidayGroup: null,
        holidayImpact: null,
        isBridgeDay: false,
        isFirstBridgeDay: false,
        seasonBlockId: null,
        seasonBlockName: null,
        pricingCategoryId: null,
        pricingCategoryLevel: null,
        matchedPricingRuleId: null,
        isQuincenaAdjacent: false,
        notes: null,
    );

    expect($analysis->isHoliday)->toBeFalse()
        ->and($analysis->holidayDefinitionId)->toBeNull()
        ->and($analysis->pricingCategoryId)->toBeNull()
        ->and($analysis->notes)->toBeNull();
});
