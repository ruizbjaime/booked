<?php

use App\Actions\Calendar\DeleteSeasonBlock;
use App\Domain\Calendar\Enums\PricingRuleType;
use App\Domain\Calendar\Enums\SeasonStrategy;
use App\Models\PricingCategory;
use App\Models\PricingRule;
use App\Models\SeasonBlock;
use App\Models\SystemSetting;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('deletes an unreferenced fixed range season block', function () {
    $admin = makeAdmin();
    $seasonBlock = SeasonBlock::factory()->fixedRange(6, 1, 6, 30)->create();

    app(DeleteSeasonBlock::class)->handle($admin, $seasonBlock);

    expect(SeasonBlock::query()->whereKey($seasonBlock->id)->exists())->toBeFalse();
});

it('marks the configuration as changed when deleting the last season block', function () {
    $admin = makeAdmin();
    $seasonBlock = SeasonBlock::factory()->fixedRange(6, 1, 6, 30)->create();

    app(DeleteSeasonBlock::class)->handle($admin, $seasonBlock);

    expect(SystemSetting::instance()->calendar_config_updated_at)->not->toBeNull();
});

it('rejects deleting managed season blocks', function () {
    $admin = makeAdmin();
    $seasonBlock = SeasonBlock::factory()->create([
        'calculation_strategy' => SeasonStrategy::HolyWeek,
    ]);

    expect(fn () => app(DeleteSeasonBlock::class)->handle($admin, $seasonBlock))
        ->toThrow(ValidationException::class);
});

it('rejects deleting season blocks referenced by pricing rules', function () {
    $admin = makeAdmin();
    $seasonBlock = SeasonBlock::factory()->fixedRange(6, 1, 6, 30)->create([
        'name' => 'mid_year_break',
    ]);
    $category = PricingCategory::factory()->create();

    PricingRule::factory()->create([
        'pricing_category_id' => $category->id,
        'rule_type' => PricingRuleType::SeasonDays,
        'conditions' => ['season_block_id' => $seasonBlock->id],
    ]);

    expect(fn () => app(DeleteSeasonBlock::class)->handle($admin, $seasonBlock))
        ->toThrow(ValidationException::class);
});

it('requires authorization to delete season blocks', function () {
    $guest = makeGuest();
    $seasonBlock = SeasonBlock::factory()->fixedRange(6, 1, 6, 30)->create();

    expect(fn () => app(DeleteSeasonBlock::class)->handle($guest, $seasonBlock))
        ->toThrow(AuthorizationException::class);
});
