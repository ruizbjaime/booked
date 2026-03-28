<?php

use App\Models\CalendarDay;
use App\Models\HolidayDefinition;
use App\Models\PricingCategory;
use App\Models\SeasonBlock;
use Carbon\CarbonImmutable;

it('belongs to a holiday definition', function () {
    $holiday = HolidayDefinition::factory()->fixed()->create();
    $day = CalendarDay::factory()->holiday()->create([
        'holiday_definition_id' => $holiday->id,
    ]);

    expect($day->holidayDefinition->id)->toBe($holiday->id);
});

it('belongs to a season block', function () {
    $block = SeasonBlock::factory()->create();
    $day = CalendarDay::factory()->create([
        'season_block_id' => $block->id,
        'season_block_name' => $block->name,
    ]);

    expect($day->seasonBlock->id)->toBe($block->id);
});

it('belongs to a pricing category', function () {
    $category = PricingCategory::factory()->create();
    $day = CalendarDay::factory()->create([
        'pricing_category_id' => $category->id,
        'pricing_category_level' => $category->level,
    ]);

    expect($day->pricingCategory->id)->toBe($category->id);
});

it('scopes by year', function () {
    $date2026 = CarbonImmutable::createStrict(2026, 3, 15);
    $date2027 = CarbonImmutable::createStrict(2027, 3, 15);

    CalendarDay::factory()->forDate($date2026)->create();
    CalendarDay::factory()->forDate($date2027)->create();

    expect(CalendarDay::query()->forYear(2026)->count())->toBe(1)
        ->and(CalendarDay::query()->forYear(2027)->count())->toBe(1);
});

it('scopes by month', function () {
    $jan = CarbonImmutable::createStrict(2026, 1, 10);
    $feb = CarbonImmutable::createStrict(2026, 2, 10);

    CalendarDay::factory()->forDate($jan)->create();
    CalendarDay::factory()->forDate($feb)->create();

    expect(CalendarDay::query()->forMonth(2026, 1)->count())->toBe(1)
        ->and(CalendarDay::query()->forMonth(2026, 2)->count())->toBe(1);
});

it('scopes by date range', function () {
    CalendarDay::factory()->forDate(CarbonImmutable::createStrict(2026, 1, 1))->create();
    CalendarDay::factory()->forDate(CarbonImmutable::createStrict(2026, 1, 15))->create();
    CalendarDay::factory()->forDate(CarbonImmutable::createStrict(2026, 2, 1))->create();

    expect(CalendarDay::query()->forDateRange('2026-01-01', '2026-01-31')->count())->toBe(2);
});

it('scopes holidays only', function () {
    CalendarDay::factory()->forDate(CarbonImmutable::createStrict(2026, 7, 1))->holiday()->create();
    CalendarDay::factory()->forDate(CarbonImmutable::createStrict(2026, 7, 2))->create(['is_holiday' => false]);

    expect(CalendarDay::query()->holidays()->count())->toBe(1);
});

it('casts boolean fields correctly', function () {
    $day = CalendarDay::factory()->create([
        'is_holiday' => true,
        'is_bridge_day' => true,
        'is_quincena_adjacent' => false,
    ]);

    expect($day->is_holiday)->toBeTrue()->toBeBool()
        ->and($day->is_bridge_day)->toBeTrue()->toBeBool()
        ->and($day->is_quincena_adjacent)->toBeFalse()->toBeBool();
});

it('casts date fields correctly', function () {
    $day = CalendarDay::factory()->create([
        'holiday_original_date' => '2026-01-01',
        'holiday_observed_date' => '2026-01-05',
    ]);

    expect($day->date)->toBeInstanceOf(CarbonImmutable::class)
        ->and($day->holiday_original_date)->toBeInstanceOf(CarbonImmutable::class)
        ->and($day->holiday_observed_date)->toBeInstanceOf(CarbonImmutable::class);
});
