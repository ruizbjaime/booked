<?php

use App\Actions\Bedrooms\DetachBedTypeFromBedroom;
use App\Models\Bedroom;
use App\Models\BedType;
use App\Models\Property;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('removes the bed type association from the bedroom', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();
    $bedroom = Bedroom::factory()->create(['property_id' => $property->id]);
    $bedType = BedType::factory()->create(['is_active' => true]);

    $bedroom->bedTypes()->attach($bedType->id, ['quantity' => 2]);

    app(DetachBedTypeFromBedroom::class)->handle($host, $bedroom, $bedType);

    expect($bedroom->fresh()->bedTypes)->toBeEmpty();
});

it('denies users who cannot update the property owner bedroom', function () {
    $guest = makeGuest();
    $bedroom = Bedroom::factory()->create();
    $bedType = BedType::factory()->create(['is_active' => true]);

    app(DetachBedTypeFromBedroom::class)->handle($guest, $bedroom, $bedType);
})->throws(AuthorizationException::class);
