<?php

use App\Actions\FeeTypes\DeleteFeeType;
use App\Models\FeeType;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('throws authorization exception when non-admin user deletes a fee type', function () {
    $guest = makeGuest();
    $feeType = FeeType::factory()->create();

    app(DeleteFeeType::class)->handle($guest, $feeType);
})->throws(AuthorizationException::class);

it('deletes an existing fee type', function () {
    $admin = makeAdmin();
    $feeType = FeeType::factory()->create();

    app(DeleteFeeType::class)->handle($admin, $feeType);

    expect(FeeType::query()->find($feeType->id))->toBeNull();
});

it('throws when the fee type no longer exists at delete time', function () {
    $admin = makeAdmin();
    $feeType = FeeType::factory()->create();
    $feeType->delete();

    app(DeleteFeeType::class)->handle($admin, $feeType);
})->throws(ModelNotFoundException::class);
