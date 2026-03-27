<?php

use App\Domain\Calendar\Enums\HolidayGroup;
use App\Models\HolidayDefinition;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->actingAs(makeAdmin());
});

it('renders successfully in create mode', function () {
    Livewire::test('calendar.holiday-definition-form', ['context' => ['mode' => 'create']])
        ->assertStatus(200)
        ->assertSet('mode', 'create')
        ->assertSet('group', 'fixed');
});

it('renders successfully in edit mode with existing values', function () {
    $holiday = HolidayDefinition::factory()->fixed()->create([
        'name' => 'independence_day',
        'en_name' => 'Independence Day',
        'es_name' => 'Día de la Independencia',
        'month' => 7,
        'day' => 20,
        'sort_order' => 5,
    ]);

    Livewire::test('calendar.holiday-definition-form', ['context' => ['mode' => 'edit', 'holidayDefinitionId' => $holiday->id]])
        ->assertSet('name', 'independence_day')
        ->assertSet('en_name', 'Independence Day')
        ->assertSet('es_name', 'Día de la Independencia')
        ->assertSet('group', 'fixed')
        ->assertSet('month', 7)
        ->assertSet('day', 20)
        ->assertSet('sort_order', 5);
});

it('creates a fixed holiday definition', function () {
    Livewire::test('calendar.holiday-definition-form', ['context' => ['mode' => 'create']])
        ->set('name', 'test_holiday')
        ->set('en_name', 'Test Holiday')
        ->set('es_name', 'Festivo de Prueba')
        ->set('group', HolidayGroup::Fixed->value)
        ->set('month', 12)
        ->set('day', 25)
        ->set('sort_order', 10)
        ->set('is_active', true)
        ->call('save')
        ->assertDispatched('close-form-modal')
        ->assertDispatched('holiday-definition-saved');

    $holiday = HolidayDefinition::query()->where('name', 'test_holiday')->first();
    expect($holiday)->not->toBeNull()
        ->and($holiday->group)->toBe(HolidayGroup::Fixed)
        ->and($holiday->month)->toBe(12)
        ->and($holiday->day)->toBe(25)
        ->and($holiday->moves_to_monday)->toBeFalse();
});

it('creates an emiliani holiday definition with forced moves_to_monday', function () {
    Livewire::test('calendar.holiday-definition-form', ['context' => ['mode' => 'create']])
        ->set('name', 'emiliani_test')
        ->set('en_name', 'Emiliani Test')
        ->set('es_name', 'Prueba Emiliani')
        ->set('group', HolidayGroup::Emiliani->value)
        ->set('month', 6)
        ->set('day', 29)
        ->set('sort_order', 11)
        ->set('is_active', true)
        ->call('save')
        ->assertDispatched('close-form-modal');

    $holiday = HolidayDefinition::query()->where('name', 'emiliani_test')->first();
    expect($holiday)->not->toBeNull()
        ->and($holiday->group)->toBe(HolidayGroup::Emiliani)
        ->and($holiday->moves_to_monday)->toBeTrue()
        ->and($holiday->easter_offset)->toBeNull();
});

it('creates an easter-based holiday definition', function () {
    Livewire::test('calendar.holiday-definition-form', ['context' => ['mode' => 'create']])
        ->set('name', 'easter_test')
        ->set('en_name', 'Easter Test')
        ->set('es_name', 'Prueba Pascua')
        ->set('group', HolidayGroup::EasterBased->value)
        ->set('easter_offset', -2)
        ->set('moves_to_monday', false)
        ->set('sort_order', 12)
        ->set('is_active', true)
        ->call('save')
        ->assertDispatched('close-form-modal');

    $holiday = HolidayDefinition::query()->where('name', 'easter_test')->first();
    expect($holiday)->not->toBeNull()
        ->and($holiday->group)->toBe(HolidayGroup::EasterBased)
        ->and($holiday->easter_offset)->toBe(-2)
        ->and($holiday->month)->toBeNull()
        ->and($holiday->day)->toBeNull();
});

it('accepts leap day for fixed holidays', function () {
    Livewire::test('calendar.holiday-definition-form', ['context' => ['mode' => 'create']])
        ->set('name', 'leap_day')
        ->set('en_name', 'Leap Day')
        ->set('es_name', 'Dia Bisiesto')
        ->set('group', HolidayGroup::Fixed->value)
        ->set('month', 2)
        ->set('day', 29)
        ->set('sort_order', 13)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('holiday-definition-saved');

    expect(HolidayDefinition::query()->where('name', 'leap_day')->exists())->toBeTrue();
});

it('edits an existing holiday definition', function () {
    $holiday = HolidayDefinition::factory()->fixed()->create([
        'name' => 'new_year',
        'en_name' => 'New Year',
        'es_name' => 'Año Nuevo',
        'month' => 1,
        'day' => 1,
    ]);

    Livewire::test('calendar.holiday-definition-form', ['context' => ['mode' => 'edit', 'holidayDefinitionId' => $holiday->id]])
        ->set('en_name', 'New Year Updated')
        ->set('es_name', 'Año Nuevo Actualizado')
        ->call('save')
        ->assertDispatched('close-form-modal')
        ->assertDispatched('holiday-definition-saved');

    $holiday->refresh();
    expect($holiday->en_name)->toBe('New Year Updated')
        ->and($holiday->es_name)->toBe('Año Nuevo Actualizado');
});

it('validates required fields for fixed holidays', function () {
    Livewire::test('calendar.holiday-definition-form', ['context' => ['mode' => 'create']])
        ->set('name', 'incomplete')
        ->set('en_name', 'Incomplete')
        ->set('es_name', 'Incompleto')
        ->set('group', HolidayGroup::Fixed->value)
        ->set('month', null)
        ->set('day', null)
        ->set('sort_order', 1)
        ->call('save')
        ->assertHasErrors(['month', 'day']);
});

it('validates invalid month and day combinations for fixed holidays', function () {
    Livewire::test('calendar.holiday-definition-form', ['context' => ['mode' => 'create']])
        ->set('name', 'invalid_date')
        ->set('en_name', 'Invalid Date')
        ->set('es_name', 'Fecha Invalida')
        ->set('group', HolidayGroup::Fixed->value)
        ->set('month', 4)
        ->set('day', 31)
        ->set('sort_order', 1)
        ->call('save')
        ->assertHasErrors(['month']);
});

it('validates easter_offset is required for easter-based holidays', function () {
    Livewire::test('calendar.holiday-definition-form', ['context' => ['mode' => 'create']])
        ->set('name', 'easter_incomplete')
        ->set('en_name', 'Easter Incomplete')
        ->set('es_name', 'Pascua Incompleta')
        ->set('group', HolidayGroup::EasterBased->value)
        ->set('easter_offset', null)
        ->set('sort_order', 1)
        ->call('save')
        ->assertHasErrors('easter_offset');
});

it('validates base impact weights as JSON', function () {
    Livewire::test('calendar.holiday-definition-form', ['context' => ['mode' => 'create']])
        ->set('name', 'invalid_json_weights')
        ->set('en_name', 'Invalid JSON Weights')
        ->set('es_name', 'Pesos JSON Invalidos')
        ->set('group', HolidayGroup::Fixed->value)
        ->set('month', 5)
        ->set('day', 1)
        ->set('base_impact_weights_json', '{"default": }')
        ->set('sort_order', 1)
        ->call('save')
        ->assertHasErrors(['base_impact_weights']);
});

it('validates special overrides as JSON when provided', function () {
    Livewire::test('calendar.holiday-definition-form', ['context' => ['mode' => 'create']])
        ->set('name', 'invalid_json_override')
        ->set('en_name', 'Invalid JSON Override')
        ->set('es_name', 'Excepcion JSON Invalida')
        ->set('group', HolidayGroup::Fixed->value)
        ->set('month', 5)
        ->set('day', 2)
        ->set('special_overrides_json', '{"dates": }')
        ->set('sort_order', 1)
        ->call('save')
        ->assertHasErrors(['special_overrides']);
});
