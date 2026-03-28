<?php

use App\Actions\FeeTypes\ToggleFeeTypeActiveStatus;
use App\Models\FeeType;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('allows an admin to deactivate a fee type', function () {
    $admin = makeAdmin();
    $feeType = FeeType::factory()->create(['is_active' => true]);

    app(ToggleFeeTypeActiveStatus::class)->handle($admin, $feeType, false);

    expect($feeType->fresh()->is_active)->toBeFalse();
});

it('allows an admin to activate a fee type', function () {
    $admin = makeAdmin();
    $feeType = FeeType::factory()->create(['is_active' => false]);

    app(ToggleFeeTypeActiveStatus::class)->handle($admin, $feeType, true);

    expect($feeType->fresh()->is_active)->toBeTrue();
});

it('forbids non admin users from toggling a fee type', function () {
    $guest = makeGuest();
    $feeType = FeeType::factory()->create(['is_active' => true]);

    expect(fn () => app(ToggleFeeTypeActiveStatus::class)->handle($guest, $feeType, false))
        ->toThrow(AuthorizationException::class);
});
