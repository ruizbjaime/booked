<?php

use App\Actions\Calendar\EditSeasonBlock;
use App\Domain\Calendar\Enums\SeasonStrategy;
use App\Models\SeasonBlock;
use App\Models\SystemSetting;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('updates a custom fixed range season block', function () {
    $admin = makeAdmin();
    $seasonBlock = SeasonBlock::factory()->fixedRange(6, 1, 6, 30)->create([
        'name' => 'mid_year_break',
    ]);

    $updated = app(EditSeasonBlock::class)->handle($admin, $seasonBlock, [
        'name' => 'mid_year_holiday',
        'en_name' => 'Mid-year Holiday',
        'es_name' => 'Receso de Mitad de Año',
        'calculation_strategy' => SeasonStrategy::FixedRange->value,
        'fixed_start_month' => 6,
        'fixed_start_day' => 5,
        'fixed_end_month' => 6,
        'fixed_end_day' => 28,
        'priority' => 9,
        'sort_order' => 9,
        'is_active' => false,
    ]);

    expect($updated->name)->toBe('mid_year_holiday')
        ->and($updated->fixed_start_day)->toBe(5)
        ->and($updated->fixed_end_day)->toBe(28)
        ->and($updated->priority)->toBe(9)
        ->and($updated->is_active)->toBeFalse()
        ->and(SystemSetting::instance()->calendar_config_updated_at)->not->toBeNull()
        ->and(SystemSetting::instance()->calendar_config_updated_at?->toDateTimeString())->toBe($updated->updated_at?->toDateTimeString());
});

it('preserves managed season configuration while updating built in blocks', function () {
    $admin = makeAdmin();
    $seasonBlock = SeasonBlock::factory()->create([
        'name' => 'holy_week',
        'calculation_strategy' => SeasonStrategy::HolyWeek,
        'fixed_start_month' => null,
        'fixed_start_day' => null,
        'fixed_end_month' => null,
        'fixed_end_day' => null,
    ]);

    $updated = app(EditSeasonBlock::class)->handle($admin, $seasonBlock, [
        'name' => 'holy_week',
        'en_name' => 'Holy Week Updated',
        'es_name' => 'Semana Santa',
        'calculation_strategy' => SeasonStrategy::HolyWeek->value,
        'fixed_start_month' => 1,
        'fixed_start_day' => 1,
        'fixed_end_month' => 1,
        'fixed_end_day' => 10,
        'priority' => 3,
        'sort_order' => 4,
        'is_active' => true,
    ]);

    expect($updated->calculation_strategy)->toBe(SeasonStrategy::HolyWeek)
        ->and($updated->fixed_start_month)->toBeNull()
        ->and($updated->fixed_end_day)->toBeNull()
        ->and($updated->en_name)->toBe('Holy Week Updated');
});

it('rejects changing the strategy of a managed season block', function () {
    $admin = makeAdmin();
    $seasonBlock = SeasonBlock::factory()->create([
        'calculation_strategy' => SeasonStrategy::HolyWeek,
    ]);

    expect(fn () => app(EditSeasonBlock::class)->handle($admin, $seasonBlock, [
        'name' => $seasonBlock->name,
        'en_name' => $seasonBlock->en_name,
        'es_name' => $seasonBlock->es_name,
        'calculation_strategy' => SeasonStrategy::FixedRange->value,
        'fixed_start_month' => 6,
        'fixed_start_day' => 1,
        'fixed_end_month' => 6,
        'fixed_end_day' => 30,
        'priority' => $seasonBlock->priority,
        'sort_order' => $seasonBlock->sort_order,
        'is_active' => $seasonBlock->is_active,
    ]))->toThrow(ValidationException::class);
});

it('requires authorization to edit season blocks', function () {
    $guest = makeGuest();
    $seasonBlock = SeasonBlock::factory()->fixedRange(6, 1, 6, 30)->create();

    expect(fn () => app(EditSeasonBlock::class)->handle($guest, $seasonBlock, [
        'name' => $seasonBlock->name,
        'en_name' => $seasonBlock->en_name,
        'es_name' => $seasonBlock->es_name,
        'calculation_strategy' => SeasonStrategy::FixedRange->value,
        'fixed_start_month' => 6,
        'fixed_start_day' => 1,
        'fixed_end_month' => 6,
        'fixed_end_day' => 30,
        'priority' => $seasonBlock->priority,
        'sort_order' => $seasonBlock->sort_order,
        'is_active' => $seasonBlock->is_active,
    ]))->toThrow(AuthorizationException::class);
});
