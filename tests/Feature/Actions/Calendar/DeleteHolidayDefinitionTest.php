<?php

use App\Actions\Calendar\DeleteHolidayDefinition;
use App\Models\HolidayDefinition;
use App\Models\SystemSetting;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('marks calendar configuration as changed when deleting the last holiday definition', function () {
    $admin = makeAdmin();
    $holiday = HolidayDefinition::factory()->fixed()->create();

    app(DeleteHolidayDefinition::class)->handle($admin, $holiday);

    expect(HolidayDefinition::query()->count())->toBe(0)
        ->and(SystemSetting::instance()->calendar_config_updated_at)->not->toBeNull();
});

it('stamps the latest remaining holiday definition after deletion', function () {
    $admin = makeAdmin();
    $older = HolidayDefinition::factory()->fixed()->create(['updated_at' => now()->subDay()]);
    $latest = HolidayDefinition::factory()->fixed()->create(['updated_at' => now()]);

    app(DeleteHolidayDefinition::class)->handle($admin, $older);

    $refreshedLatest = $latest->fresh();

    expect(HolidayDefinition::query()->pluck('id')->all())->toBe([$latest->id])
        ->and($refreshedLatest)->not->toBeNull()
        ->and($refreshedLatest?->updated_at?->toDateTimeString())->toBe(SystemSetting::instance()->calendar_config_updated_at?->toDateTimeString());
});
