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
