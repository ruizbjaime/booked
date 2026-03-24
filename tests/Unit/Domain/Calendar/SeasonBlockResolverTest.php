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

it('resolves Holy Week from Palm Sunday to Easter Sunday', function () {
    $blocks = [new SeasonBlockData(1, 'holy_week', SeasonStrategy::HolyWeek, priority: 1)];

    $ranges = $this->resolver->resolve($blocks, 2026, $this->easter2026, $this->holidays2026);

    expect($ranges)->toHaveCount(1)
        ->and($ranges[0]->name)->toBe('holy_week')
        ->and($ranges[0]->start->toDateString())->toBe('2026-03-29') // Palm Sunday
        ->and($ranges[0]->end->toDateString())->toBe('2026-04-05') // Easter Sunday
        ->and($ranges[0]->priority)->toBe(1);
});

it('resolves Year-End block crossing into next year', function () {
    $blocks = [new SeasonBlockData(2, 'year_end', SeasonStrategy::YearEnd, priority: 2)];

    $easter2027 = EasterCalculator::forYear(2027);
    $nextYearHolidays = $this->holidayResolver->resolve(allColombianHolidayDefinitions(), 2027, $easter2027);

    $ranges = $this->resolver->resolve($blocks, 2026, $this->easter2026, $this->holidays2026, $nextYearHolidays);

    expect($ranges)->toHaveCount(1)
        ->and($ranges[0]->name)->toBe('year_end')
        ->and($ranges[0]->start->toDateString())->toBe('2026-12-15');

    // Jan 6, 2027 is Wednesday → Emiliani moves to Jan 11 (Monday)
    expect($ranges[0]->end->toDateString())->toBe('2027-01-11')
        ->and($ranges[0]->end->isMonday())->toBeTrue();
});

it('resolves October Recess around Columbus Day', function () {
    $blocks = [new SeasonBlockData(3, 'october_recess', SeasonStrategy::OctoberRecess, priority: 3)];

    $ranges = $this->resolver->resolve($blocks, 2026, $this->easter2026, $this->holidays2026);

    expect($ranges)->toHaveCount(1)
        ->and($ranges[0]->name)->toBe('october_recess');

    // Oct 12, 2026 is Monday (Emiliani stays) → Sat Oct 10 through Sun Oct 18
    $columbusDay = collect($this->holidays2026)->firstWhere('name', 'columbus_day');
    expect($columbusDay->observedDate->toDateString())->toBe('2026-10-12')
        ->and($ranges[0]->start->isSaturday())->toBeTrue()
        ->and($ranges[0]->end->isSunday())->toBeTrue();
});

it('resolves Foreign Tourist season Jan 15 to end of Feb', function () {
    $blocks = [new SeasonBlockData(4, 'foreign_tourist', SeasonStrategy::ForeignTourist, priority: 4)];

    $ranges = $this->resolver->resolve($blocks, 2026, $this->easter2026, $this->holidays2026);

    expect($ranges)->toHaveCount(1)
        ->and($ranges[0]->name)->toBe('foreign_tourist')
        ->and($ranges[0]->start->toDateString())->toBe('2026-01-15')
        ->and($ranges[0]->end->toDateString())->toBe('2026-02-28');
});

it('handles leap year for Foreign Tourist season', function () {
    $blocks = [new SeasonBlockData(4, 'foreign_tourist', SeasonStrategy::ForeignTourist, priority: 4)];
    $easter2028 = EasterCalculator::forYear(2028);

    $ranges = $this->resolver->resolve($blocks, 2028, $easter2028, []);

    expect($ranges[0]->end->toDateString())->toBe('2028-02-29');
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
