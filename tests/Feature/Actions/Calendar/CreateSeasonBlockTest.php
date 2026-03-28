<?php

use App\Actions\Calendar\CreateSeasonBlock;
use App\Domain\Calendar\Enums\SeasonStrategy;
use App\Models\SeasonBlock;
use App\Models\SystemSetting;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('creates a custom fixed range season block and updates the freshness marker', function () {
    $admin = makeAdmin();

    $seasonBlock = app(CreateSeasonBlock::class)->handle($admin, [
        'name' => 'mid_year_break',
        'en_name' => 'Mid-year Break',
        'es_name' => 'Receso de Mitad de Año',
        'calculation_strategy' => SeasonStrategy::FixedRange->value,
        'fixed_start_month' => 6,
        'fixed_start_day' => 1,
        'fixed_end_month' => 6,
        'fixed_end_day' => 30,
        'priority' => 8,
        'sort_order' => 8,
        'is_active' => true,
    ]);

    expect($seasonBlock)->toBeInstanceOf(SeasonBlock::class)
        ->and($seasonBlock->calculation_strategy)->toBe(SeasonStrategy::FixedRange)
        ->and(SystemSetting::instance()->calendar_config_updated_at)->not->toBeNull();
});

it('rejects non fixed range strategies for custom season blocks', function () {
    $admin = makeAdmin();

    expect(fn () => app(CreateSeasonBlock::class)->handle($admin, [
        'name' => 'holy_week_custom',
        'en_name' => 'Holy Week Custom',
        'es_name' => 'Semana Santa Personalizada',
        'calculation_strategy' => SeasonStrategy::HolyWeek->value,
        'priority' => 8,
        'sort_order' => 8,
        'is_active' => true,
    ]))->toThrow(ValidationException::class);
});

it('requires authorization to create season blocks', function () {
    $guest = makeGuest();

    expect(fn () => app(CreateSeasonBlock::class)->handle($guest, [
        'name' => 'mid_year_break',
        'en_name' => 'Mid-year Break',
        'es_name' => 'Receso de Mitad de Año',
        'calculation_strategy' => SeasonStrategy::FixedRange->value,
        'fixed_start_month' => 6,
        'fixed_start_day' => 1,
        'fixed_end_month' => 6,
        'fixed_end_day' => 30,
        'priority' => 8,
        'sort_order' => 8,
        'is_active' => true,
    ]))->toThrow(AuthorizationException::class);
});
