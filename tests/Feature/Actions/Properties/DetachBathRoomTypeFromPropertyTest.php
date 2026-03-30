<?php

use App\Actions\Properties\DetachBathRoomTypeFromProperty;
use App\Models\BathRoomType;
use App\Models\Property;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('removes the shared bathroom type association from the property', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();
    $bathRoomType = BathRoomType::factory()->create();

    $property->bathRoomTypes()->attach($bathRoomType->id, ['quantity' => 2]);

    app(DetachBathRoomTypeFromProperty::class)->handle($host, $property, $bathRoomType);

    expect($property->fresh()->bathRoomTypes)->toBeEmpty();
});

it('denies users who cannot update the property shared bathroom types', function () {
    $guest = makeGuest();
    $property = Property::factory()->create();
    $bathRoomType = BathRoomType::factory()->create();

    app(DetachBathRoomTypeFromProperty::class)->handle($guest, $property, $bathRoomType);
})->throws(AuthorizationException::class);
