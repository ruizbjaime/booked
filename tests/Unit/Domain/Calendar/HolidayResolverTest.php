<?php

use App\Domain\Calendar\Data\HolidayDefinitionData;
use App\Domain\Calendar\Enums\HolidayGroup;
use App\Domain\Calendar\Services\EasterCalculator;
use App\Domain\Calendar\Services\HolidayResolver;

beforeEach(function () {
    $this->resolver = new HolidayResolver;
    $this->easter2026 = EasterCalculator::forYear(2026); // Apr 5
});

it('resolves a fixed holiday with day-of-week impact weights', function () {
    $definitions = [
        new HolidayDefinitionData(
            id: 1,
            name: 'independence_day',
            group: HolidayGroup::Fixed,
            month: 7,
            day: 20,
            easterOffset: null,
            movesToMonday: false,
            baseImpactWeights: [
                'monday' => 9.5, 'tuesday' => 7.5, 'wednesday' => 4.0,
                'thursday' => 7.5, 'friday' => 9.5, 'saturday' => 2.0, 'sunday' => 2.0,
            ],
        ),
    ];

    $holidays = $this->resolver->resolve($definitions, 2026, $this->easter2026);

    expect($holidays)->toHaveCount(1);

    $holiday = $holidays[0];
    // Jul 20, 2026 is Monday
    expect($holiday->name)->toBe('independence_day')
        ->and($holiday->group)->toBe(HolidayGroup::Fixed)
        ->and($holiday->originalDate->toDateString())->toBe('2026-07-20')
        ->and($holiday->observedDate->toDateString())->toBe('2026-07-20')
        ->and($holiday->impact)->toBe(9.5)
        ->and($holiday->wasMoved)->toBeFalse();
});

it('resolves fixed holiday impact by day of week', function (int $year, string $expectedDay, float $expectedImpact) {
    $definitions = [
        new HolidayDefinitionData(
            id: 1,
            name: 'christmas',
            group: HolidayGroup::Fixed,
            month: 12,
            day: 25,
            easterOffset: null,
            movesToMonday: false,
            baseImpactWeights: [
                'monday' => 9.5, 'tuesday' => 7.5, 'wednesday' => 4.0,
                'thursday' => 7.5, 'friday' => 9.5, 'saturday' => 2.0, 'sunday' => 2.0,
            ],
        ),
    ];

    $easter = EasterCalculator::forYear($year);
    $holidays = $this->resolver->resolve($definitions, $year, $easter);

    expect($holidays[0]->impact)->toBe($expectedImpact)
        ->and($holidays[0]->observedDate->format('l'))->toBe($expectedDay);
})->with([
    '2026 (Friday)' => [2026, 'Friday', 9.5],
    '2025 (Thursday)' => [2025, 'Thursday', 7.5],
    '2024 (Wednesday)' => [2024, 'Wednesday', 4.0],
]);

it('moves Emiliani holidays to next Monday', function () {
    $definitions = [
        new HolidayDefinitionData(
            id: 1,
            name: 'epiphany',
            group: HolidayGroup::Emiliani,
            month: 1,
            day: 6,
            easterOffset: null,
            movesToMonday: true,
            baseImpactWeights: ['default' => 9.5],
        ),
    ];

    // Jan 6, 2026 is Tuesday → moves to Monday Jan 12
    $holidays = $this->resolver->resolve($definitions, 2026, $this->easter2026);

    expect($holidays[0]->originalDate->toDateString())->toBe('2026-01-06')
        ->and($holidays[0]->observedDate->toDateString())->toBe('2026-01-12')
        ->and($holidays[0]->observedDate->isMonday())->toBeTrue()
        ->and($holidays[0]->wasMoved)->toBeTrue()
        ->and($holidays[0]->impact)->toBe(9.5);
});

it('does not move Emiliani holiday already on Monday', function () {
    $definitions = [
        new HolidayDefinitionData(
            id: 1,
            name: 'epiphany',
            group: HolidayGroup::Emiliani,
            month: 1,
            day: 6,
            easterOffset: null,
            movesToMonday: true,
            baseImpactWeights: ['default' => 9.5],
        ),
    ];

    // Jan 6, 2025 is Monday
    $easter2025 = EasterCalculator::forYear(2025);
    $holidays = $this->resolver->resolve($definitions, 2025, $easter2025);

    expect($holidays[0]->observedDate->toDateString())->toBe('2025-01-06')
        ->and($holidays[0]->wasMoved)->toBeFalse();
});

it('resolves Easter-based holidays with correct offsets', function () {
    $definitions = [
        new HolidayDefinitionData(
            id: 1,
            name: 'holy_thursday',
            group: HolidayGroup::EasterBased,
            month: null,
            day: null,
            easterOffset: -3,
            movesToMonday: false,
            baseImpactWeights: ['default' => 10.0],
        ),
        new HolidayDefinitionData(
            id: 2,
            name: 'good_friday',
            group: HolidayGroup::EasterBased,
            month: null,
            day: null,
            easterOffset: -2,
            movesToMonday: false,
            baseImpactWeights: ['default' => 10.0],
        ),
    ];

    // Easter 2026 = Apr 5
    $holidays = $this->resolver->resolve($definitions, 2026, $this->easter2026);

    expect($holidays[0]->observedDate->toDateString())->toBe('2026-04-02')
        ->and($holidays[0]->wasMoved)->toBeFalse()
        ->and($holidays[1]->observedDate->toDateString())->toBe('2026-04-03')
        ->and($holidays[1]->wasMoved)->toBeFalse();
});

it('moves Easter-based holiday to Monday when configured', function () {
    $definitions = [
        new HolidayDefinitionData(
            id: 1,
            name: 'ascension',
            group: HolidayGroup::EasterBased,
            month: null,
            day: null,
            easterOffset: 39,
            movesToMonday: true,
            baseImpactWeights: ['default' => 9.5],
        ),
    ];

    // Easter 2026 = Apr 5 → +39 = May 14 (Thursday) → next Monday = May 18
    $holidays = $this->resolver->resolve($definitions, 2026, $this->easter2026);

    expect($holidays[0]->originalDate->toDateString())->toBe('2026-05-14')
        ->and($holidays[0]->observedDate->toDateString())->toBe('2026-05-18')
        ->and($holidays[0]->observedDate->isMonday())->toBeTrue()
        ->and($holidays[0]->wasMoved)->toBeTrue();
});

it('resolves all 18 Colombian holidays for 2026', function () {
    $definitions = allColombianHolidayDefinitions();

    $holidays = $this->resolver->resolve($definitions, 2026, $this->easter2026);

    expect($holidays)->toHaveCount(18);

    $byName = collect($holidays)->keyBy('name');

    // Fixed holidays keep their date
    expect($byName['new_year']->observedDate->toDateString())->toBe('2026-01-01')
        ->and($byName['labor_day']->observedDate->toDateString())->toBe('2026-05-01')
        ->and($byName['christmas']->observedDate->toDateString())->toBe('2026-12-25');

    // Emiliani holidays move to Monday
    expect($byName['epiphany']->observedDate->isMonday())->toBeTrue()
        ->and($byName['saints_peter_and_paul']->observedDate->isMonday())->toBeTrue();

    // Easter-based
    expect($byName['holy_thursday']->observedDate->toDateString())->toBe('2026-04-02')
        ->and($byName['good_friday']->observedDate->toDateString())->toBe('2026-04-03');
});
