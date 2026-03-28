<?php

use App\Domain\Calendar\Enums\HolidayGroup;
use App\Models\CalendarDay;
use App\Models\HolidayDefinition;
use Carbon\CarbonImmutable;

it('returns localized holiday name, column, and accessor for both locales', function () {
    $holiday = HolidayDefinition::factory()->fixed()->create([
        'en_name' => 'Independence Day',
        'es_name' => 'Dia de la Independencia',
    ]);

    app()->setLocale('en');

    expect($holiday->localizedName())->toBe('Independence Day')
        ->and(HolidayDefinition::localizedNameColumn())->toBe('en_name')
        ->and($holiday->localized_name_attribute)->toBe('Independence Day');

    app()->setLocale('es');

    expect($holiday->localizedName())->toBe('Dia de la Independencia')
        ->and(HolidayDefinition::localizedNameColumn())->toBe('es_name')
        ->and($holiday->localized_name_attribute)->toBe('Dia de la Independencia');
});

it('casts holiday attributes and filters active records', function () {
    HolidayDefinition::factory()->fixed()->create(['is_active' => true]);
    HolidayDefinition::factory()->fixed()->create(['is_active' => false]);

    $holiday = HolidayDefinition::factory()->emiliani()->create([
        'group' => HolidayGroup::Emiliani,
        'moves_to_monday' => true,
        'base_impact_weights' => ['default' => 7, 'holiday' => 10],
        'special_overrides' => [['location' => 'co', 'dates' => ['2026-01-01'], 'impact' => 9]],
        'is_active' => true,
    ])->fresh();

    expect(HolidayDefinition::query()->active()->count())->toBe(2)
        ->and($holiday->group)->toBe(HolidayGroup::Emiliani)
        ->and($holiday->moves_to_monday)->toBeTrue()
        ->and($holiday->moves_to_monday)->toBeBool()
        ->and($holiday->base_impact_weights)->toBe(['default' => 7, 'holiday' => 10])
        ->and($holiday->special_overrides)->toBe([['location' => 'co', 'dates' => ['2026-01-01'], 'impact' => 9]])
        ->and($holiday->is_active)->toBeTrue();
});

it('exposes related calendar days through the relationship', function () {
    $holiday = HolidayDefinition::factory()->fixed()->create();
    $day = CalendarDay::factory()->holiday()->forDate(CarbonImmutable::parse('2026-07-20'))->create([
        'holiday_definition_id' => $holiday->id,
    ]);

    expect($holiday->calendarDays)->toHaveCount(1)
        ->and($holiday->calendarDays->first()->is($day))->toBeTrue();
});
