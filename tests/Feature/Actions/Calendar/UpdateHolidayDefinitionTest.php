<?php

use App\Actions\Calendar\UpdateHolidayDefinition;
use App\Models\HolidayDefinition;
use App\Models\SystemSetting;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('updates holiday definition fields using normalized values', function () {
    $admin = makeAdmin();
    $holiday = HolidayDefinition::factory()->fixed()->create([
        'name' => 'new_year',
        'en_name' => 'New Year',
        'base_impact_weights' => ['default' => 10],
    ]);

    app(UpdateHolidayDefinition::class)->handle($admin, $holiday, 'name', ' LABOR_DAY ');
    app(UpdateHolidayDefinition::class)->handle($admin, $holiday, 'en_name', ' Labor Day ');
    app(UpdateHolidayDefinition::class)->handle($admin, $holiday, 'base_impact_weights', '{"default":12}');
    app(UpdateHolidayDefinition::class)->handle($admin, $holiday, 'is_active', false);

    $fresh = $holiday->fresh();

    expect($fresh->name)->toBe('labor_day')
        ->and($fresh->en_name)->toBe('Labor Day')
        ->and($fresh->base_impact_weights)->toBe(['default' => 12])
        ->and($fresh->is_active)->toBeFalse()
        ->and(SystemSetting::instance()->calendar_config_updated_at)->not->toBeNull();
});

it('requires valid decoded arrays for holiday json fields', function () {
    $admin = makeAdmin();
    $holiday = HolidayDefinition::factory()->fixed()->create();

    expect(fn () => app(UpdateHolidayDefinition::class)->handle($admin, $holiday, 'base_impact_weights', '{bad-json'))
        ->toThrow(ValidationException::class);
});

it('requires authorization to update a holiday definition', function () {
    $guest = makeGuest();
    $holiday = HolidayDefinition::factory()->fixed()->create();

    expect(fn () => app(UpdateHolidayDefinition::class)->handle($guest, $holiday, 'en_name', 'Holiday'))
        ->toThrow(AuthorizationException::class);
});

it('aborts with 422 for an unknown holiday definition field', function () {
    $admin = makeAdmin();
    $holiday = HolidayDefinition::factory()->fixed()->create();

    expect(fn () => app(UpdateHolidayDefinition::class)->handle($admin, $holiday, 'unknown_field', 'value'))
        ->toThrow(HttpException::class);
});

it('rejects holiday definition names already used by another record', function () {
    $admin = makeAdmin();
    HolidayDefinition::factory()->fixed()->create(['name' => 'labor_day']);
    $holiday = HolidayDefinition::factory()->fixed()->create(['name' => 'new_year']);

    expect(fn () => app(UpdateHolidayDefinition::class)->handle($admin, $holiday, 'name', 'labor_day'))
        ->toThrow(ValidationException::class);
});

it('updates additional holiday scalar fields and special overrides', function () {
    $admin = makeAdmin();
    $holiday = HolidayDefinition::factory()->fixed()->create([
        'special_overrides' => null,
    ]);

    app(UpdateHolidayDefinition::class)->handle($admin, $holiday, 'es_name', ' Festivo ');
    app(UpdateHolidayDefinition::class)->handle($admin, $holiday, 'month', 5);
    app(UpdateHolidayDefinition::class)->handle($admin, $holiday, 'day', 1);
    app(UpdateHolidayDefinition::class)->handle($admin, $holiday, 'easter_offset', 12);
    app(UpdateHolidayDefinition::class)->handle($admin, $holiday, 'moves_to_monday', true);
    app(UpdateHolidayDefinition::class)->handle($admin, $holiday, 'sort_order', 7);
    app(UpdateHolidayDefinition::class)->handle($admin, $holiday, 'special_overrides', '[{"location":"beach","dates":["05-01"],"impact":9}]');

    $fresh = $holiday->fresh();

    expect($fresh->es_name)->toBe('Festivo')
        ->and($fresh->month)->toBe(5)
        ->and($fresh->day)->toBe(1)
        ->and($fresh->easter_offset)->toBe(12)
        ->and($fresh->moves_to_monday)->toBeTrue()
        ->and($fresh->sort_order)->toBe(7)
        ->and($fresh->special_overrides)->toBe([
            ['location' => 'beach', 'dates' => ['05-01'], 'impact' => 9],
        ]);
});

it('rejects invalid special overrides payloads', function () {
    $admin = makeAdmin();
    $holiday = HolidayDefinition::factory()->fixed()->create();

    expect(fn () => app(UpdateHolidayDefinition::class)->handle($admin, $holiday, 'special_overrides', '{bad-json'))
        ->toThrow(ValidationException::class);
});

it('accepts pre-decoded arrays for holiday json fields without re-decoding', function () {
    $admin = makeAdmin();
    $holiday = HolidayDefinition::factory()->fixed()->create([
        'base_impact_weights' => ['default' => 10],
    ]);

    app(UpdateHolidayDefinition::class)->handle($admin, $holiday, 'base_impact_weights', ['default' => 15]);

    expect($holiday->fresh()->base_impact_weights)->toBe(['default' => 15]);
});
