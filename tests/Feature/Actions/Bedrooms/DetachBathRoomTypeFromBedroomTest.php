<?php

use App\Actions\Bedrooms\DetachBathRoomTypeFromBedroom;
use App\Models\BathRoomType;
use App\Models\Bedroom;
use App\Models\Property;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('removes the bathroom type association from the bedroom', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();
    $bedroom = Bedroom::factory()->create(['property_id' => $property->id]);
    $bathRoomType = BathRoomType::factory()->create();

    $bedroom->bathRoomTypes()->attach($bathRoomType->id, ['quantity' => 2]);

    app(DetachBathRoomTypeFromBedroom::class)->handle($host, $bedroom, $bathRoomType);

    expect($bedroom->fresh()->bathRoomTypes)->toBeEmpty();
});

it('denies users who cannot update the property owner bedroom', function () {
    $guest = makeGuest();
    $bedroom = Bedroom::factory()->create();
    $bathRoomType = BathRoomType::factory()->create();

    app(DetachBathRoomTypeFromBedroom::class)->handle($guest, $bedroom, $bathRoomType);
})->throws(AuthorizationException::class);
