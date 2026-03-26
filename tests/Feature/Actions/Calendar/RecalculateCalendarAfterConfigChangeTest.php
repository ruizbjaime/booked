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
    // Generate an initial 4-year range (2026-2029)
    app(GenerateCalendarDays::class)->handle(
        CarbonImmutable::createStrict(2026, 1, 1),
        CarbonImmutable::createStrict(2029, 12, 31),
    );

    $expectedDays = CalendarDay::count();

    // Regenerate — should cover the full 2026-2029 range
    $count = app(RecalculateCalendarAfterConfigChange::class)->handle();

    expect($count)->toBe($expectedDays)
        ->and(CalendarDay::query()->distinct('year')->pluck('year')->sort()->values()->all())
        ->toBe([2026, 2027, 2028, 2029]);
});

it('updates days in the furthest year during regeneration', function () {
    // Generate 2026-2030
    app(GenerateCalendarDays::class)->handle(
        CarbonImmutable::createStrict(2026, 1, 1),
        CarbonImmutable::createStrict(2030, 12, 31),
    );

    // Tamper with a day in the furthest year to verify it gets refreshed
    $farDay = CalendarDay::query()->where('year', 2030)->where('month', 6)->first();
    $originalCategory = $farDay->pricing_category_id;
    $farDay->updateQuietly(['pricing_category_id' => null]);

    app(RecalculateCalendarAfterConfigChange::class)->handle();

    expect($farDay->fresh()->pricing_category_id)->toBe($originalCategory);
});
