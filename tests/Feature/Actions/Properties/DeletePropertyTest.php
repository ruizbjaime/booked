<?php

use App\Actions\Properties\DeleteProperty;
use App\Models\Property;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('throws authorization exception when non-host deletes a property', function () {
    $guest = makeGuest();
    $property = Property::factory()->create();

    app(DeleteProperty::class)->handle($guest, $property);
})->throws(AuthorizationException::class);

it('deletes an existing property', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();

    app(DeleteProperty::class)->handle($host, $property);

    expect(Property::query()->find($property->id))->toBeNull();
});

it('throws when the property no longer exists at delete time', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();
    $property->delete();

    app(DeleteProperty::class)->handle($host, $property);
})->throws(ModelNotFoundException::class);
