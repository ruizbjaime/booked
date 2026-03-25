<?php

use App\Actions\Calendar\GenerateCalendarDays;
use App\Models\CalendarDay;
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

test('generates 365 days for a non-leap year', function () {
    $action = app(GenerateCalendarDays::class);
    $from = CarbonImmutable::createStrict(2026, 1, 1);
    $to = CarbonImmutable::createStrict(2026, 12, 31);

    $count = $action->handle($from, $to);

    expect($count)->toBe(365)
        ->and(CalendarDay::query()->forYear(2026)->count())->toBe(365);
});

test('generates 366 days for a leap year', function () {
    $action = app(GenerateCalendarDays::class);
    $from = CarbonImmutable::createStrict(2028, 1, 1);
    $to = CarbonImmutable::createStrict(2028, 12, 31);

    $count = $action->handle($from, $to);

    expect($count)->toBe(366)
        ->and(CalendarDay::query()->forYear(2028)->count())->toBe(366);
});

test('marks holidays correctly', function () {
    $action = app(GenerateCalendarDays::class);
    $action->handle(
        CarbonImmutable::createStrict(2026, 1, 1),
        CarbonImmutable::createStrict(2026, 12, 31),
    );

    $holidays = CalendarDay::query()->forYear(2026)->holidays()->get();

    expect($holidays->count())->toBe(18);

    // New Year's Day
    $newYear = CalendarDay::query()->where('date', '2026-01-01')->first();
    expect($newYear->is_holiday)->toBeTrue()
        ->and($newYear->holiday_group)->toBe('fixed');
});

test('marks bridge days around Monday holidays', function () {
    $action = app(GenerateCalendarDays::class);
    $action->handle(
        CarbonImmutable::createStrict(2026, 7, 1),
        CarbonImmutable::createStrict(2026, 7, 31),
    );

    // Jul 20, 2026 is Monday → bridge Fri 17, Sat 18, Sun 19
    $bridgeFri = CalendarDay::query()->where('date', '2026-07-17')->first();
    $bridgeSat = CalendarDay::query()->where('date', '2026-07-18')->first();
    $bridgeSun = CalendarDay::query()->where('date', '2026-07-19')->first();

    expect($bridgeFri->is_bridge_day)->toBeTrue()
        ->and($bridgeSat->is_bridge_day)->toBeTrue()
        ->and($bridgeSun->is_bridge_day)->toBeTrue();
});

test('assigns correct pricing categories', function () {
    $action = app(GenerateCalendarDays::class);
    $action->handle(
        CarbonImmutable::createStrict(2026, 1, 1),
        CarbonImmutable::createStrict(2026, 12, 31),
    );

    // Dec 7 → CAT 1
    $dec7 = CalendarDay::query()->where('date', '2026-12-07')->first();
    expect($dec7->pricing_category_level)->toBe(1);

    // Dec 31 → CAT 1
    $dec31 = CalendarDay::query()->where('date', '2026-12-31')->first();
    expect($dec31->pricing_category_level)->toBe(1);

    // A regular Tuesday in May → CAT 4
    $may5 = CalendarDay::query()->where('date', '2026-05-05')->first();
    expect($may5->pricing_category_level)->toBe(4);

    // A regular Friday in May → CAT 3
    $may8 = CalendarDay::query()->where('date', '2026-05-08')->first();
    expect($may8->pricing_category_level)->toBe(3);

    // Easter Sunday checkout stays in economy
    $apr5 = CalendarDay::query()->where('date', '2026-04-05')->first();
    expect($apr5->is_bridge_day)->toBeFalse()
        ->and($apr5->pricing_category_level)->toBe(4);

    // Fixed holiday on Friday uses Thursday-Friday-Saturday as bridge pattern
    $dec24 = CalendarDay::query()->where('date', '2026-12-24')->first();
    $dec25 = CalendarDay::query()->where('date', '2026-12-25')->first();
    $dec26 = CalendarDay::query()->where('date', '2026-12-26')->first();
    $dec27 = CalendarDay::query()->where('date', '2026-12-27')->first();
    expect($dec24->pricing_category_level)->toBe(2)
        ->and($dec25->pricing_category_level)->toBe(2)
        ->and($dec26->pricing_category_level)->toBe(2)
        ->and($dec27->pricing_category_level)->toBe(4);
});

test('upsert is idempotent', function () {
    $action = app(GenerateCalendarDays::class);
    $from = CarbonImmutable::createStrict(2026, 1, 1);
    $to = CarbonImmutable::createStrict(2026, 1, 31);

    $action->handle($from, $to);
    $firstCount = CalendarDay::query()->forMonth(2026, 1)->count();

    $action->handle($from, $to);
    $secondCount = CalendarDay::query()->forMonth(2026, 1)->count();

    expect($firstCount)->toBe(31)
        ->and($secondCount)->toBe(31);
});

test('every day has a pricing category assigned', function () {
    $action = app(GenerateCalendarDays::class);
    $action->handle(
        CarbonImmutable::createStrict(2026, 1, 1),
        CarbonImmutable::createStrict(2026, 12, 31),
    );

    $withoutPricing = CalendarDay::query()
        ->forYear(2026)
        ->whereNull('pricing_category_id')
        ->count();

    expect($withoutPricing)->toBe(0);
});

test('quincena adjacent days are marked correctly', function () {
    $action = app(GenerateCalendarDays::class);
    $action->handle(
        CarbonImmutable::createStrict(2026, 3, 1),
        CarbonImmutable::createStrict(2026, 3, 31),
    );

    $mar15 = CalendarDay::query()->where('date', '2026-03-15')->first();
    $mar10 = CalendarDay::query()->where('date', '2026-03-10')->first();
    $mar31 = CalendarDay::query()->where('date', '2026-03-31')->first();

    expect($mar15->is_quincena_adjacent)->toBeTrue()
        ->and($mar10->is_quincena_adjacent)->toBeFalse()
        ->and($mar31->is_quincena_adjacent)->toBeTrue();
});

test('progress callback is called', function () {
    $action = app(GenerateCalendarDays::class);
    $callCount = 0;

    $action->handle(
        CarbonImmutable::createStrict(2026, 1, 1),
        CarbonImmutable::createStrict(2026, 12, 31),
        function () use (&$callCount): void {
            $callCount++;
        },
    );

    expect($callCount)->toBeGreaterThan(0);
});
