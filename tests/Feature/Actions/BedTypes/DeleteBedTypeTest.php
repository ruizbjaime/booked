<?php

use App\Actions\BedTypes\DeleteBedType;
use App\Models\BedType;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('throws authorization exception when non-admin user deletes a bed type', function () {
    $guest = makeGuest();
    $bedType = BedType::factory()->create();

    app(DeleteBedType::class)->handle($guest, $bedType);
})->throws(AuthorizationException::class);

it('deletes an existing bed type', function () {
    $admin = makeAdmin();
    $bedType = BedType::factory()->create();

    app(DeleteBedType::class)->handle($admin, $bedType);

    expect(BedType::query()->find($bedType->id))->toBeNull();
});

it('throws when the bed type no longer exists at delete time', function () {
    $admin = makeAdmin();
    $bedType = BedType::factory()->create();
    $bedType->delete();

    app(DeleteBedType::class)->handle($admin, $bedType);
})->throws(ModelNotFoundException::class);
