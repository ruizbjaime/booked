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

    app(GenerateCalendarDays::class)->handle(
        CarbonImmutable::createStrict(2026, 1, 1),
        CarbonImmutable::createStrict(2026, 12, 31),
    );
});

// --- Easter 2026 verification ---

test('Easter 2026 falls on April 5', function () {
    $easter = CalendarDay::query()->where('date', '2026-04-05')->first();

    expect($easter->is_holiday)->toBeFalse(); // Easter Sunday is not a Colombian public holiday
});

// --- Holy Week ---

test('Holy Thursday 2026 is April 2 with CAT 1', function () {
    $day = CalendarDay::query()->where('date', '2026-04-02')->first();

    expect($day->is_holiday)->toBeTrue()
        ->and($day->holiday_group)->toBe('easter_based')
        ->and($day->pricing_category_level)->toBe(1);
});

test('Good Friday 2026 is April 3 with CAT 1', function () {
    $day = CalendarDay::query()->where('date', '2026-04-03')->first();

    expect($day->is_holiday)->toBeTrue()
        ->and($day->holiday_group)->toBe('easter_based')
        ->and($day->pricing_category_level)->toBe(1);
});

test('Holy Week non-premium days run from March 27 through April 1 as CAT 2', function () {
    $preFriday = CalendarDay::query()->where('date', '2026-03-27')->first();
    $holyWednesday = CalendarDay::query()->where('date', '2026-04-01')->first();

    expect($preFriday->season_block_name)->toBe('holy_week')
        ->and($preFriday->pricing_category_level)->toBe(2)
        ->and($holyWednesday->season_block_name)->toBe('holy_week')
        ->and($holyWednesday->pricing_category_level)->toBe(2);
});

test('Holy Saturday 2026 remains premium and Easter Sunday is outside holy week', function () {
    $holySaturday = CalendarDay::query()->where('date', '2026-04-04')->first();
    $easter = CalendarDay::query()->where('date', '2026-04-05')->first();

    expect($holySaturday->season_block_name)->toBe('holy_week')
        ->and($holySaturday->pricing_category_level)->toBe(1)
        ->and($easter->season_block_name)->toBeNull()
        ->and($easter->is_bridge_day)->toBeFalse()
        ->and($easter->pricing_category_level)->toBe(4);
});

test('Easter Sunday is not in holy week season', function () {
    $easter = CalendarDay::query()->where('date', '2026-04-05')->first();

    expect($easter->season_block_name)->not->toBe('holy_week');
});

// --- Emiliani holidays ---

test('Epiphany 2026 (Jan 6 Tuesday) moves to Monday Jan 12', function () {
    $jan12 = CalendarDay::query()->where('date', '2026-01-12')->first();

    expect($jan12->is_holiday)->toBeTrue()
        ->and($jan12->holiday_group)->toBe('emiliani')
        ->and($jan12->holiday_original_date->toDateString())->toBe('2026-01-06')
        ->and($jan12->holiday_observed_date->toDateString())->toBe('2026-01-12');
});

// --- Fixed holidays ---

test('Independence Day Jul 20 2026 is Monday with impact 9.5 and CAT 2', function () {
    $day = CalendarDay::query()->where('date', '2026-07-20')->first();

    expect($day->is_holiday)->toBeTrue()
        ->and($day->holiday_group)->toBe('fixed')
        ->and((float) $day->holiday_impact)->toBe(9.5)
        ->and($day->day_of_week_name)->toBe('monday')
        ->and($day->pricing_category_level)->toBe(2);
});

test('New Year 2026 is Thursday with impact 7.5 and falls to economy', function () {
    $day = CalendarDay::query()->where('date', '2026-01-01')->first();

    expect($day->is_holiday)->toBeTrue()
        ->and((float) $day->holiday_impact)->toBe(7.5)
        ->and($day->day_of_week_name)->toBe('thursday')
        ->and($day->pricing_category_level)->toBe(4);
});

test('Bridge weekend before Jul 20 Monday holiday: first day CAT 3, rest CAT 2', function () {
    $fri = CalendarDay::query()->where('date', '2026-07-17')->first();
    $sat = CalendarDay::query()->where('date', '2026-07-18')->first();
    $sun = CalendarDay::query()->where('date', '2026-07-19')->first();

    expect($fri->is_bridge_day)->toBeTrue()
        ->and($fri->pricing_category_level)->toBe(3) // First bridge day
        ->and($sat->is_bridge_day)->toBeTrue()
        ->and($sat->pricing_category_level)->toBe(2)
        ->and($sun->is_bridge_day)->toBeTrue()
        ->and($sun->pricing_category_level)->toBe(2);
});

test('Fixed Friday holiday bridge: first day CAT 3, rest CAT 2, Sunday fallback', function () {
    $thu = CalendarDay::query()->where('date', '2026-12-24')->first();
    $fri = CalendarDay::query()->where('date', '2026-12-25')->first();
    $sat = CalendarDay::query()->where('date', '2026-12-26')->first();
    $sun = CalendarDay::query()->where('date', '2026-12-27')->first();

    expect($thu->is_bridge_day)->toBeTrue()
        ->and($thu->pricing_category_level)->toBe(3) // First bridge day
        ->and($fri->is_bridge_day)->toBeTrue()
        ->and($fri->pricing_category_level)->toBe(2)
        ->and($sat->is_bridge_day)->toBeTrue()
        ->and($sat->pricing_category_level)->toBe(2)
        ->and($sun->is_bridge_day)->toBeFalse()
        ->and($sun->pricing_category_level)->toBe(4);
});

// --- Villa de Leyva special dates ---

test('Dec 7-8 are always CAT 1 (Villa de Leyva)', function () {
    $dec7 = CalendarDay::query()->where('date', '2026-12-07')->first();
    $dec8 = CalendarDay::query()->where('date', '2026-12-08')->first();

    expect($dec7->pricing_category_level)->toBe(1)
        ->and($dec8->pricing_category_level)->toBe(1);
});

// --- Christmas ---

test('Christmas 2026 is Friday with impact 9.5', function () {
    $day = CalendarDay::query()->where('date', '2026-12-25')->first();

    expect($day->is_holiday)->toBeTrue()
        ->and((float) $day->holiday_impact)->toBe(9.5)
        ->and($day->day_of_week_name)->toBe('friday');
});

// --- New Year's Eve ---

test('Dec 31 is CAT 1', function () {
    $day = CalendarDay::query()->where('date', '2026-12-31')->first();

    expect($day->pricing_category_level)->toBe(1);
});

// --- Normal weekends ---

test('Regular Friday outside season is CAT 3', function () {
    // May 8 is a Friday, not in any season
    $day = CalendarDay::query()->where('date', '2026-05-08')->first();

    expect($day->pricing_category_level)->toBe(3)
        ->and($day->day_of_week_name)->toBe('friday');
});

// --- Economy fallback ---

test('Regular weekday outside season is CAT 4', function () {
    // May 5 is a Tuesday, not in any season
    $day = CalendarDay::query()->where('date', '2026-05-05')->first();

    expect($day->pricing_category_level)->toBe(4)
        ->and($day->day_of_week_name)->toBe('tuesday');
});

// --- December season ---

test('December season runs from early December through the Thursday before Reyes bridge', function () {
    $dec1 = CalendarDay::query()->where('date', '2026-12-01')->first();
    $dec15 = CalendarDay::query()->where('date', '2026-12-15')->first();
    $dec31 = CalendarDay::query()->where('date', '2026-12-31')->first();

    expect($dec1->season_block_name)->toBe('december_season')
        ->and($dec15->season_block_name)->toBe('december_season')
        ->and($dec31->season_block_name)->toBe('december_season');
});

// --- Counts ---

test('2026 has exactly 18 public holidays', function () {
    expect(CalendarDay::query()->forYear(2026)->holidays()->count())->toBe(18);
});

test('all 365 days have a pricing category', function () {
    $unpricedCount = CalendarDay::query()
        ->forYear(2026)
        ->whereNull('pricing_category_level')
        ->count();

    expect($unpricedCount)->toBe(0);
});
