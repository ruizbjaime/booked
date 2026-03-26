<?php

use App\Actions\Calendar\GenerateCalendarDays;
use App\Actions\Calendar\RecalculateCalendarAfterConfigChange;
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

it('regenerates all existing years, not just the default two', function () {
    app(GenerateCalendarDays::class)->handle(
        CarbonImmutable::createStrict(2026, 1, 1),
        CarbonImmutable::createStrict(2028, 12, 31),
    );

    $expectedDays = CalendarDay::count();
    $count = app(RecalculateCalendarAfterConfigChange::class)->handle();

    expect($count)->toBe($expectedDays)
        ->and(CalendarDay::query()->distinct('year')->pluck('year')->sort()->values()->all())
        ->toBe([2026, 2027, 2028]);
});

it('updates days in the furthest year during regeneration', function () {
    app(GenerateCalendarDays::class)->handle(
        CarbonImmutable::createStrict(2026, 1, 1),
        CarbonImmutable::createStrict(2028, 12, 31),
    );

    $farDay = CalendarDay::query()->where('year', 2028)->where('month', 6)->first();
    $originalCategory = $farDay->pricing_category_id;
    $farDay->updateQuietly(['pricing_category_id' => null]);

    app(RecalculateCalendarAfterConfigChange::class)->handle();

    expect($farDay->fresh()->pricing_category_id)->toBe($originalCategory);
});
