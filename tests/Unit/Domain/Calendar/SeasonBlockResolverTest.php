<?php

use App\Domain\Calendar\Data\SeasonBlockData;
use App\Domain\Calendar\Enums\SeasonStrategy;
use App\Domain\Calendar\Services\EasterCalculator;
use App\Domain\Calendar\Services\HolidayResolver;
use App\Domain\Calendar\Services\SeasonBlockResolver;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->resolver = new SeasonBlockResolver;
    $this->easter2026 = EasterCalculator::forYear(2026); // Apr 5
    $this->holidayResolver = new HolidayResolver;
    $this->holidays2026 = $this->holidayResolver->resolve(allColombianHolidayDefinitions(), 2026, $this->easter2026);
});

it('resolves Holy Week from Friday before Palm Sunday to Holy Saturday', function () {
    $blocks = [new SeasonBlockData(1, 'holy_week', SeasonStrategy::HolyWeek, priority: 1)];

    $ranges = $this->resolver->resolve($blocks, 2026, $this->easter2026, $this->holidays2026);

    expect($ranges)->toHaveCount(1)
        ->and($ranges[0]->name)->toBe('holy_week')
        ->and($ranges[0]->start->toDateString())->toBe('2026-03-27') // Friday before Palm Sunday
        ->and($ranges[0]->start->isFriday())->toBeTrue()
        ->and($ranges[0]->end->toDateString())->toBe('2026-04-04') // Holy Saturday
        ->and($ranges[0]->end->isSaturday())->toBeTrue()
        ->and($ranges[0]->priority)->toBe(1);
});

it('resolves December Season through the Thursday before observed Epiphany Monday', function () {
    $blocks = [new SeasonBlockData(2, 'december_season', SeasonStrategy::DecemberSeason, priority: 2)];

    $easter2027 = EasterCalculator::forYear(2027);
    $nextYearHolidays = $this->holidayResolver->resolve(allColombianHolidayDefinitions(), 2027, $easter2027);

    $ranges = $this->resolver->resolve($blocks, 2026, $this->easter2026, $this->holidays2026, $nextYearHolidays);

    expect($ranges)->toHaveCount(1)
        ->and($ranges[0]->name)->toBe('december_season')
        ->and($ranges[0]->start->toDateString())->toBe('2026-12-01')
        ->and($ranges[0]->end->toDateString())->toBe('2027-01-07')
        ->and($ranges[0]->end->isThursday())->toBeTrue();
});

it('resolves October Recess as the week prior to Columbus Day long weekend', function () {
    $blocks = [new SeasonBlockData(3, 'october_recess', SeasonStrategy::OctoberRecess, priority: 3)];

    $ranges = $this->resolver->resolve($blocks, 2026, $this->easter2026, $this->holidays2026);

    expect($ranges)->toHaveCount(1)
        ->and($ranges[0]->name)->toBe('october_recess');

    // Oct 12, 2026 is Monday (Emiliani stays) → recess: Fri Oct 2 through Sun Oct 11
    $columbusDay = collect($this->holidays2026)->firstWhere('name', 'columbus_day');
    expect($columbusDay->observedDate->toDateString())->toBe('2026-10-12')
        ->and($ranges[0]->start->toDateString())->toBe('2026-10-02')
        ->and($ranges[0]->start->isFriday())->toBeTrue()
        ->and($ranges[0]->end->toDateString())->toBe('2026-10-11')
        ->and($ranges[0]->end->isSunday())->toBeTrue();
});

it('resolves Fixed Range strategy', function () {
    $block = new SeasonBlockData(
        id: 10,
        name: 'custom_season',
        calculationStrategy: SeasonStrategy::FixedRange,
        fixedStartMonth: 6,
        fixedStartDay: 1,
        fixedEndMonth: 6,
        fixedEndDay: 30,
        priority: 5,
    );

    $ranges = $this->resolver->resolve([$block], 2026, $this->easter2026, $this->holidays2026);

    expect($ranges)->toHaveCount(1)
        ->and($ranges[0]->start->toDateString())->toBe('2026-06-01')
        ->and($ranges[0]->end->toDateString())->toBe('2026-06-30');
});

it('returns null for October Recess when Columbus Day is missing', function () {
    $blocks = [new SeasonBlockData(3, 'october_recess', SeasonStrategy::OctoberRecess, priority: 3)];

    $ranges = $this->resolver->resolve($blocks, 2026, $this->easter2026, []);

    expect($ranges)->toBeEmpty();
});

it('returns no ranges for Fixed Range block with null date fields', function () {
    $block = new SeasonBlockData(
        id: 20,
        name: 'incomplete_fixed',
        calculationStrategy: SeasonStrategy::FixedRange,
        fixedStartMonth: null,
        fixedStartDay: null,
        fixedEndMonth: null,
        fixedEndDay: null,
        priority: 4,
    );

    $ranges = $this->resolver->resolve([$block], 2026, $this->easter2026, $this->holidays2026);

    expect($ranges)->toBeEmpty();
});

it('SeasonBlockRange contains method works correctly', function () {
    $blocks = [new SeasonBlockData(1, 'holy_week', SeasonStrategy::HolyWeek, priority: 1)];
    $ranges = $this->resolver->resolve($blocks, 2026, $this->easter2026, $this->holidays2026);
    $holyWeek = $ranges[0];

    $insideDate = CarbonImmutable::createStrict(2026, 4, 2); // Holy Thursday
    $outsideDate = CarbonImmutable::createStrict(2026, 4, 10);
    $startDate = CarbonImmutable::createStrict(2026, 3, 29); // Palm Sunday

    expect($holyWeek->contains($insideDate))->toBeTrue()
        ->and($holyWeek->contains($outsideDate))->toBeFalse()
        ->and($holyWeek->contains($startDate))->toBeTrue();
});
