<?php

use App\Actions\Calendar\BuildHolidayDefinitionPayload;
use App\Domain\Calendar\Enums\HolidayGroup;
use App\Models\HolidayDefinition;
use Illuminate\Validation\ValidationException;

it('normalizes a fixed holiday payload', function () {
    $payload = app(BuildHolidayDefinitionPayload::class)->handle([
        'name' => '  NEW_YEAR  ',
        'en_name' => '  New Year ',
        'es_name' => ' Año Nuevo  ',
        'group' => HolidayGroup::Fixed->value,
        'month' => '1',
        'day' => '1',
        'moves_to_monday' => true,
        'base_impact_weights' => '{"default": 10}',
        'special_overrides' => '[{"location":"beach","dates":["2026-01-01"],"impact":12}]',
        'sort_order' => '7',
        'is_active' => '1',
    ]);

    expect($payload)->toMatchArray([
        'name' => 'new_year',
        'en_name' => 'New Year',
        'es_name' => 'Año Nuevo',
        'group' => HolidayGroup::Fixed->value,
        'month' => 1,
        'day' => 1,
        'easter_offset' => null,
        'moves_to_monday' => false,
        'base_impact_weights' => ['default' => 10],
        'sort_order' => 7,
        'is_active' => true,
    ])->and($payload['special_overrides'])->toBeArray();
});

it('forces emiliani holidays to move to monday', function () {
    $payload = app(BuildHolidayDefinitionPayload::class)->handle([
        'name' => 'saint_joseph',
        'en_name' => 'Saint Joseph',
        'es_name' => 'San Jose',
        'group' => HolidayGroup::Emiliani->value,
        'month' => 3,
        'day' => 19,
        'moves_to_monday' => false,
        'base_impact_weights' => ['default' => 10],
        'sort_order' => 3,
        'is_active' => true,
    ]);

    expect($payload['moves_to_monday'])->toBeTrue()
        ->and($payload['easter_offset'])->toBeNull();
});

it('requires a valid date for date based holiday groups', function () {
    expect(fn () => app(BuildHolidayDefinitionPayload::class)->handle([
        'name' => 'invalid_date',
        'en_name' => 'Invalid Date',
        'es_name' => 'Fecha Invalida',
        'group' => HolidayGroup::Fixed->value,
        'month' => 2,
        'day' => 31,
        'base_impact_weights' => ['default' => 10],
        'sort_order' => 1,
        'is_active' => true,
    ]))->toThrow(ValidationException::class);
});

it('requires an easter offset for easter based holidays', function () {
    expect(fn () => app(BuildHolidayDefinitionPayload::class)->handle([
        'name' => 'holy_thursday',
        'en_name' => 'Holy Thursday',
        'es_name' => 'Jueves Santo',
        'group' => HolidayGroup::EasterBased->value,
        'base_impact_weights' => ['default' => 10],
        'sort_order' => 5,
        'is_active' => true,
    ]))->toThrow(ValidationException::class);
});

it('rejects invalid json fields', function () {
    expect(fn () => app(BuildHolidayDefinitionPayload::class)->handle([
        'name' => 'bad_json',
        'en_name' => 'Bad Json',
        'es_name' => 'Json Malo',
        'group' => HolidayGroup::Fixed->value,
        'month' => 1,
        'day' => 6,
        'base_impact_weights' => '{invalid-json',
        'sort_order' => 6,
        'is_active' => true,
    ]))->toThrow(ValidationException::class);
});

it('ignores the current holiday when checking unique names during edits', function () {
    $holiday = HolidayDefinition::factory()->fixed()->create([
        'name' => 'new_year',
        'en_name' => 'New Year',
        'es_name' => 'Año Nuevo',
    ]);

    $payload = app(BuildHolidayDefinitionPayload::class)->handle([
        'name' => 'NEW_YEAR',
        'en_name' => 'New Year Holiday',
        'es_name' => 'Festivo de Año Nuevo',
        'group' => HolidayGroup::Fixed->value,
        'month' => 1,
        'day' => 1,
        'base_impact_weights' => ['default' => 15],
        'sort_order' => 1,
        'is_active' => true,
    ], $holiday);

    expect($payload['name'])->toBe('new_year')
        ->and($payload['base_impact_weights'])->toBe(['default' => 15]);
});

it('requires month and day for date-based holiday groups', function () {
    expect(fn () => app(BuildHolidayDefinitionPayload::class)->handle([
        'name' => 'missing_date',
        'en_name' => 'Missing Date',
        'es_name' => 'Fecha Faltante',
        'group' => HolidayGroup::Fixed->value,
        'base_impact_weights' => ['default' => 10],
        'sort_order' => 1,
        'is_active' => true,
    ]))->toThrow(ValidationException::class);
});

it('requires base impact weights for holiday definitions', function () {
    expect(fn () => app(BuildHolidayDefinitionPayload::class)->handle([
        'name' => 'missing_weights',
        'en_name' => 'Missing Weights',
        'es_name' => 'Pesos Faltantes',
        'group' => HolidayGroup::Fixed->value,
        'month' => 1,
        'day' => 1,
        'sort_order' => 1,
        'is_active' => true,
    ]))->toThrow(ValidationException::class);
});
