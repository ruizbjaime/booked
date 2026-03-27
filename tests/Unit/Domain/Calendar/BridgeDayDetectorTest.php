<?php

use App\Domain\Calendar\Services\BridgeDayDetector;
use App\Domain\Calendar\Services\EasterCalculator;
use App\Domain\Calendar\Services\HolidayResolver;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->detector = new BridgeDayDetector;
});

it('marks Fri/Sat/Sun as bridge days for a Monday holiday', function () {
    $resolver = new HolidayResolver;
    $easter2026 = EasterCalculator::forYear(2026);
    $holidays = $resolver->resolve(allColombianHolidayDefinitions(), 2026, $easter2026);

    // Jul 20, 2026 is Monday (independence_day)
    $independence = collect($holidays)->firstWhere('name', 'independence_day');
    expect($independence->observedDate->isMonday())->toBeTrue();

    $bridges = $this->detector->detect([$independence]);

    expect($bridges)->toHaveKey('2026-07-17') // Friday
        ->toHaveKey('2026-07-18') // Saturday
        ->toHaveKey('2026-07-19'); // Sunday
});

it('marks Thu/Fri/Sat as bridge days for a fixed holiday that falls on Friday', function () {
    $resolver = new HolidayResolver;
    $easter2026 = EasterCalculator::forYear(2026);
    $holidays = $resolver->resolve(allColombianHolidayDefinitions(), 2026, $easter2026);

    // Dec 25, 2026 is Friday
    $christmas = collect($holidays)->firstWhere('name', 'christmas');
    expect($christmas->observedDate->isFriday())->toBeTrue();

    $bridges = $this->detector->detect([$christmas]);

    expect($bridges)->toHaveKey('2026-12-24') // Thursday
        ->toHaveKey('2026-12-25') // Friday
        ->toHaveKey('2026-12-26') // Saturday
        ->not->toHaveKey('2026-12-27'); // Sunday fallback
});

it('does not mark Easter Sunday as a bridge day after Good Friday', function () {
    $resolver = new HolidayResolver;
    $easter2026 = EasterCalculator::forYear(2026);
    $holidays = $resolver->resolve(allColombianHolidayDefinitions(), 2026, $easter2026);

    $goodFriday = collect($holidays)->firstWhere('name', 'good_friday');
    expect($goodFriday->observedDate->toDateString())->toBe('2026-04-03');

    $bridges = $this->detector->detect([$goodFriday]);

    expect($bridges)->toHaveKey('2026-04-04')
        ->not->toHaveKey('2026-04-05');
});

it('does not create bridge days for mid-week holidays', function () {
    $resolver = new HolidayResolver;
    $easter2026 = EasterCalculator::forYear(2026);
    $holidays = $resolver->resolve(allColombianHolidayDefinitions(), 2026, $easter2026);

    // New Year 2026 is Thursday — no bridge
    $newYear = collect($holidays)->firstWhere('name', 'new_year');
    expect($newYear->observedDate->isThursday())->toBeTrue();

    $bridges = $this->detector->detect([$newYear]);

    expect($bridges)->toBeEmpty();
});

it('detects bridge days for all Emiliani holidays on Mondays', function () {
    $resolver = new HolidayResolver;
    $easter2026 = EasterCalculator::forYear(2026);
    $holidays = $resolver->resolve(allColombianHolidayDefinitions(), 2026, $easter2026);

    $mondayHolidays = collect($holidays)->filter(fn ($h) => $h->observedDate->isMonday());

    $bridges = $this->detector->detect($mondayHolidays->values()->all());

    foreach ($mondayHolidays as $holiday) {
        $monday = $holiday->observedDate;
        $friday = $monday->previous(CarbonImmutable::FRIDAY)->toDateString();
        $saturday = $monday->previous(CarbonImmutable::SATURDAY)->toDateString();
        $sunday = $monday->subDay()->toDateString();

        expect($bridges)->toHaveKey($friday)
            ->toHaveKey($saturday)
            ->toHaveKey($sunday);
    }
});

it('associates bridge days with the correct holiday definition ID and impact', function () {
    $resolver = new HolidayResolver;
    $easter2026 = EasterCalculator::forYear(2026);
    $holidays = $resolver->resolve(allColombianHolidayDefinitions(), 2026, $easter2026);

    $independence = collect($holidays)->firstWhere('name', 'independence_day');
    $bridges = $this->detector->detect([$independence]);

    expect($bridges['2026-07-17'])->toBe(['definitionId' => $independence->definitionId, 'impact' => $independence->impact])
        ->and($bridges['2026-07-18'])->toBe(['definitionId' => $independence->definitionId, 'impact' => $independence->impact])
        ->and($bridges['2026-07-19'])->toBe(['definitionId' => $independence->definitionId, 'impact' => $independence->impact]);
});
