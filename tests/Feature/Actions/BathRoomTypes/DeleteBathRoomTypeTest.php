<?php

use App\Actions\BathRoomTypes\DeleteBathRoomType;
use App\Models\BathRoomType;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('throws authorization exception when non-admin user deletes a bathroom type', function () {
    $guest = makeGuest();
    $bathRoomType = BathRoomType::factory()->create();

    app(DeleteBathRoomType::class)->handle($guest, $bathRoomType);
})->throws(AuthorizationException::class);

it('deletes an existing bathroom type', function () {
    $admin = makeAdmin();
    $bathRoomType = BathRoomType::factory()->create();

    app(DeleteBathRoomType::class)->handle($admin, $bathRoomType);

    expect(BathRoomType::query()->find($bathRoomType->id))->toBeNull();
});

it('throws when the bathroom type no longer exists at delete time', function () {
    $admin = makeAdmin();
    $bathRoomType = BathRoomType::factory()->create();
    $bathRoomType->delete();

    app(DeleteBathRoomType::class)->handle($admin, $bathRoomType);
})->throws(ModelNotFoundException::class);
