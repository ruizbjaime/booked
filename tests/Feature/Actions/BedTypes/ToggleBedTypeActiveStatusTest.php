<?php

use App\Actions\BedTypes\ToggleBedTypeActiveStatus;
use App\Models\BedType;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('allows an admin to deactivate a bed type', function () {
    $admin = makeAdmin();
    $bedType = BedType::factory()->create(['is_active' => true]);

    app(ToggleBedTypeActiveStatus::class)->handle($admin, $bedType, false);

    expect($bedType->fresh()->is_active)->toBeFalse();
});

it('allows an admin to activate a bed type', function () {
    $admin = makeAdmin();
    $bedType = BedType::factory()->create(['is_active' => false]);

    app(ToggleBedTypeActiveStatus::class)->handle($admin, $bedType, true);

    expect($bedType->fresh()->is_active)->toBeTrue();
});

it('forbids non admin users from toggling a bed type', function () {
    $guest = makeGuest();
    $bedType = BedType::factory()->create(['is_active' => true]);

    expect(fn () => app(ToggleBedTypeActiveStatus::class)->handle($guest, $bedType, false))
        ->toThrow(AuthorizationException::class);
});
