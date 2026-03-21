<?php

use App\Actions\ChargeBases\DeleteChargeBasis;
use App\Models\ChargeBasis;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('throws authorization exception when non-admin user deletes a charge basis', function () {
    $guest = makeGuest();
    $chargeBasis = ChargeBasis::factory()->create();

    app(DeleteChargeBasis::class)->handle($guest, $chargeBasis);
})->throws(AuthorizationException::class);

it('deletes an existing charge basis', function () {
    $admin = makeAdmin();
    $chargeBasis = ChargeBasis::factory()->create();

    app(DeleteChargeBasis::class)->handle($admin, $chargeBasis);

    expect(ChargeBasis::query()->find($chargeBasis->id))->toBeNull();
});

it('throws when the charge basis no longer exists at delete time', function () {
    $admin = makeAdmin();
    $chargeBasis = ChargeBasis::factory()->create();
    $chargeBasis->delete();

    app(DeleteChargeBasis::class)->handle($admin, $chargeBasis);
})->throws(ModelNotFoundException::class);
