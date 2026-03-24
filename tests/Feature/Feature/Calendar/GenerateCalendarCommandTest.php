<?php

use App\Models\CalendarDay;
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

test('command generates full year with year argument', function () {
    $this->artisan('calendar:generate', ['year' => 2026])
        ->assertExitCode(0);

    expect(CalendarDay::query()->forYear(2026)->count())->toBe(365);
});

test('command generates custom date range', function () {
    $this->artisan('calendar:generate', ['--from' => '2026-03-01', '--to' => '2026-03-31'])
        ->assertExitCode(0);

    expect(CalendarDay::query()->forMonth(2026, 3)->count())->toBe(31);
});

test('command rejects invalid year range', function () {
    $this->artisan('calendar:generate', ['year' => 1999])
        ->assertExitCode(1);
});

test('command rejects inverted date range', function () {
    $this->artisan('calendar:generate', ['--from' => '2026-12-31', '--to' => '2026-01-01'])
        ->assertExitCode(1);
});

test('command defaults to current and next year when no arguments', function () {
    $this->artisan('calendar:generate')
        ->assertExitCode(0);

    $currentYear = now()->year;
    $nextYear = $currentYear + 1;

    expect(CalendarDay::query()->forYear($currentYear)->count())->toBeGreaterThan(0)
        ->and(CalendarDay::query()->forYear($nextYear)->count())->toBeGreaterThan(0);
});

test('command outputs progress information', function () {
    $this->artisan('calendar:generate', ['year' => 2026])
        ->expectsOutputToContain('Generating calendar days')
        ->expectsOutputToContain('Generated 365 calendar days')
        ->assertExitCode(0);
});
