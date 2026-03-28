<?php

use App\Actions\Calendar\CreateHolidayDefinition;
use App\Models\HolidayDefinition;
use App\Models\SystemSetting;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('creates a holiday definition and updates the freshness marker', function () {
    $admin = makeAdmin();

    $holiday = app(CreateHolidayDefinition::class)->handle($admin, [
        'name' => 'labor_day',
        'en_name' => 'Labor Day',
        'es_name' => 'Dia del Trabajo',
        'group' => 'fixed',
        'month' => 5,
        'day' => 1,
        'base_impact_weights' => ['default' => 10],
        'sort_order' => 2,
        'is_active' => true,
    ]);

    expect($holiday)->toBeInstanceOf(HolidayDefinition::class)
        ->and($holiday->name)->toBe('labor_day')
        ->and($holiday->exists)->toBeTrue()
        ->and(SystemSetting::instance()->calendar_config_updated_at)->not->toBeNull();
});

it('requires authorization to create holiday definitions', function () {
    $guest = makeGuest();

    expect(fn () => app(CreateHolidayDefinition::class)->handle($guest, [
        'name' => 'labor_day',
        'en_name' => 'Labor Day',
        'es_name' => 'Dia del Trabajo',
        'group' => 'fixed',
        'month' => 5,
        'day' => 1,
        'base_impact_weights' => ['default' => 10],
        'sort_order' => 2,
        'is_active' => true,
    ]))->toThrow(AuthorizationException::class);
});
