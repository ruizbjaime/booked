<?php

use App\Actions\Calendar\DeletePricingCategory;
use App\Models\CalendarDay;
use App\Models\PricingCategory;
use App\Models\SystemSetting;
use Carbon\CarbonImmutable;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('deactivates referenced pricing categories instead of deleting them', function () {
    $admin = makeAdmin();
    $category = PricingCategory::factory()->create(['is_active' => true]);

    CalendarDay::factory()->forDate(CarbonImmutable::parse('2026-04-10'))->create([
        'pricing_category_id' => $category->id,
        'pricing_category_level' => $category->level,
    ]);

    $deleted = app(DeletePricingCategory::class)->handle($admin, $category);

    expect($deleted)->toBeFalse()
        ->and($category->fresh()?->is_active)->toBeFalse()
        ->and(SystemSetting::instance()->calendar_config_updated_at)->not->toBeNull();
});

it('physically deletes an unreferenced category and stamps the remaining one', function () {
    $admin = makeAdmin();
    $remaining = PricingCategory::factory()->create(['is_active' => true]);
    $toDelete = PricingCategory::factory()->create(['is_active' => true]);

    $deleted = app(DeletePricingCategory::class)->handle($admin, $toDelete);

    expect($deleted)->toBeTrue()
        ->and($toDelete->fresh())->toBeNull()
        ->and($remaining->fresh())->not->toBeNull()
        ->and(SystemSetting::instance()->calendar_config_updated_at)->not->toBeNull();
});

it('physically deletes the last unreferenced category and marks configuration changed', function () {
    $admin = makeAdmin();
    $category = PricingCategory::factory()->create(['is_active' => true]);

    $deleted = app(DeletePricingCategory::class)->handle($admin, $category);

    expect($deleted)->toBeTrue()
        ->and($category->fresh())->toBeNull()
        ->and(PricingCategory::query()->count())->toBe(0)
        ->and(SystemSetting::instance()->calendar_config_updated_at)->not->toBeNull();
});
