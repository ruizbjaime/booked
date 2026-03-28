<?php

use App\Actions\Calendar\EditHolidayDefinition;
use App\Models\HolidayDefinition;
use App\Models\SystemSetting;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('updates a holiday definition and refreshes the configuration marker', function () {
    $admin = makeAdmin();
    $holiday = HolidayDefinition::factory()->fixed()->create([
        'name' => 'new_year',
        'en_name' => 'New Year',
        'es_name' => 'Año Nuevo',
        'base_impact_weights' => ['default' => 10],
    ]);

    $updated = app(EditHolidayDefinition::class)->handle($admin, $holiday, [
        'name' => 'new_year',
        'en_name' => 'New Year Holiday',
        'es_name' => 'Festivo de Año Nuevo',
        'group' => 'fixed',
        'month' => 1,
        'day' => 1,
        'base_impact_weights' => ['default' => 12],
        'special_overrides' => [['location' => 'beach', 'dates' => ['2026-01-01'], 'impact' => 15]],
        'sort_order' => 9,
        'is_active' => false,
    ]);

    expect($updated->id)->toBe($holiday->id)
        ->and($updated->en_name)->toBe('New Year Holiday')
        ->and($updated->es_name)->toBe('Festivo de Año Nuevo')
        ->and($updated->base_impact_weights)->toBe(['default' => 12])
        ->and($updated->is_active)->toBeFalse()
        ->and(SystemSetting::instance()->calendar_config_updated_at)->not->toBeNull();
});

it('requires authorization to edit holiday definitions', function () {
    $guest = makeGuest();
    $holiday = HolidayDefinition::factory()->fixed()->create();

    expect(fn () => app(EditHolidayDefinition::class)->handle($guest, $holiday, [
        'name' => $holiday->name,
        'en_name' => $holiday->en_name,
        'es_name' => $holiday->es_name,
        'group' => $holiday->group->value,
        'month' => $holiday->month,
        'day' => $holiday->day,
        'base_impact_weights' => $holiday->base_impact_weights,
        'sort_order' => $holiday->sort_order,
        'is_active' => $holiday->is_active,
    ]))->toThrow(AuthorizationException::class);
});
