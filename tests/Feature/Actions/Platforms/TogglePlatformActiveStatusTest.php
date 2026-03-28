<?php

use App\Actions\Platforms\TogglePlatformActiveStatus;
use App\Models\Platform;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('activates a platform', function () {
    $admin = makeAdmin();
    $platform = Platform::factory()->create(['is_active' => false]);

    app(TogglePlatformActiveStatus::class)->handle($admin, $platform, true);

    expect($platform->fresh()->is_active)->toBeTrue();
});

it('deactivates a platform', function () {
    $admin = makeAdmin();
    $platform = Platform::factory()->create(['is_active' => true]);

    app(TogglePlatformActiveStatus::class)->handle($admin, $platform, false);

    expect($platform->fresh()->is_active)->toBeFalse();
});

it('throws authorization exception when non-admin toggles a platform', function () {
    $guest = makeGuest();
    $platform = Platform::factory()->create();

    app(TogglePlatformActiveStatus::class)->handle($guest, $platform, true);
})->throws(AuthorizationException::class);
