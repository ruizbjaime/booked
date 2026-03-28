<?php

use App\Actions\Calendar\UpdateSeasonBlock;
use App\Models\SeasonBlock;
use App\Models\SystemSetting;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('updates season block fields using normalized values', function () {
    $admin = makeAdmin();
    $seasonBlock = SeasonBlock::factory()->fixedRange(6, 1, 6, 30)->create([
        'name' => 'mid_year_break',
        'en_name' => 'Mid Year Break',
        'priority' => 2,
        'sort_order' => 2,
        'is_active' => true,
    ]);

    app(UpdateSeasonBlock::class)->handle($admin, $seasonBlock, 'name', ' MID_YEAR_HOLIDAY ');
    app(UpdateSeasonBlock::class)->handle($admin, $seasonBlock, 'en_name', ' Mid Year Holiday ');
    app(UpdateSeasonBlock::class)->handle($admin, $seasonBlock, 'priority', 8);
    app(UpdateSeasonBlock::class)->handle($admin, $seasonBlock, 'is_active', false);

    $fresh = $seasonBlock->fresh();

    expect($fresh->name)->toBe('mid_year_holiday')
        ->and($fresh->en_name)->toBe('Mid Year Holiday')
        ->and($fresh->priority)->toBe(8)
        ->and($fresh->is_active)->toBeFalse()
        ->and(SystemSetting::instance()->calendar_config_updated_at)->not->toBeNull();
});

it('rejects invalid season block priorities', function () {
    $admin = makeAdmin();
    $seasonBlock = SeasonBlock::factory()->fixedRange(6, 1, 6, 30)->create();

    expect(fn () => app(UpdateSeasonBlock::class)->handle($admin, $seasonBlock, 'priority', -1))
        ->toThrow(ValidationException::class);
});

it('requires authorization to update a season block', function () {
    $guest = makeGuest();
    $seasonBlock = SeasonBlock::factory()->fixedRange(6, 1, 6, 30)->create();

    expect(fn () => app(UpdateSeasonBlock::class)->handle($guest, $seasonBlock, 'en_name', 'Blocked'))
        ->toThrow(AuthorizationException::class);
});

it('aborts with 422 for an unknown season block field', function () {
    $admin = makeAdmin();
    $seasonBlock = SeasonBlock::factory()->fixedRange(6, 1, 6, 30)->create();

    expect(fn () => app(UpdateSeasonBlock::class)->handle($admin, $seasonBlock, 'unknown_field', 'value'))
        ->toThrow(HttpException::class);
});

it('updates additional season block text and sort order fields', function () {
    $admin = makeAdmin();
    $seasonBlock = SeasonBlock::factory()->fixedRange(6, 1, 6, 30)->create([
        'es_name' => 'Mitad de ano',
        'sort_order' => 5,
    ]);

    app(UpdateSeasonBlock::class)->handle($admin, $seasonBlock, 'es_name', ' Bloque de verano ');
    app(UpdateSeasonBlock::class)->handle($admin, $seasonBlock, 'sort_order', 9);

    expect($seasonBlock->fresh()->es_name)->toBe('Bloque de verano')
        ->and($seasonBlock->fresh()->sort_order)->toBe(9);
});
