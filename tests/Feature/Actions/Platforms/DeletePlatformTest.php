<?php

use App\Actions\Platforms\DeletePlatform;
use App\Models\Platform;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('deletes a platform', function () {
    $admin = makeAdmin();
    $platform = Platform::factory()->create();

    app(DeletePlatform::class)->handle($admin, $platform);

    expect(Platform::query()->find($platform->id))->toBeNull();
});

it('throws authorization exception when non-admin deletes a platform', function () {
    $guest = makeGuest();
    $platform = Platform::factory()->create();

    app(DeletePlatform::class)->handle($guest, $platform);
})->throws(AuthorizationException::class);
