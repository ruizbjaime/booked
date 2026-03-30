<?php

use App\Actions\Bedrooms\AttachBedTypeToBedroom;
use App\Models\Bedroom;
use App\Models\BedType;
use App\Models\Property;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

function validBedTypeBedroomInput(array $overrides = []): array
{
    return array_merge([
        'bed_type_id' => BedType::factory()->create(['is_active' => true])->id,
        'quantity' => 2,
    ], $overrides);
}

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('creates the bedroom bed type association with quantity', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();
    $bedroom = Bedroom::factory()->create(['property_id' => $property->id]);

    $input = validBedTypeBedroomInput();

    app(AttachBedTypeToBedroom::class)->handle($host, $bedroom, $input);

    expect($bedroom->fresh()->bedTypes)->toHaveCount(1)
        ->and($bedroom->fresh()->bedTypes->first()?->pivot->quantity)->toBe(2);
});

it('updates quantity when the bed type is already attached', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();
    $bedroom = Bedroom::factory()->create(['property_id' => $property->id]);
    $bedType = BedType::factory()->create(['is_active' => true]);

    $bedroom->bedTypes()->attach($bedType->id, ['quantity' => 1]);

    app(AttachBedTypeToBedroom::class)->handle($host, $bedroom, [
        'bed_type_id' => $bedType->id,
        'quantity' => 3,
    ]);

    expect($bedroom->fresh()->bedTypes)->toHaveCount(1)
        ->and($bedroom->fresh()->bedTypes->first()?->pivot->quantity)->toBe(3);
});

it('rejects inactive bed types', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();
    $bedroom = Bedroom::factory()->create(['property_id' => $property->id]);
    $inactive = BedType::factory()->create(['is_active' => false]);

    app(AttachBedTypeToBedroom::class)->handle($host, $bedroom, [
        'bed_type_id' => $inactive->id,
        'quantity' => 1,
    ]);
})->throws(ValidationException::class);

it('rejects quantity lower than one', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();
    $bedroom = Bedroom::factory()->create(['property_id' => $property->id]);

    app(AttachBedTypeToBedroom::class)->handle($host, $bedroom, validBedTypeBedroomInput([
        'quantity' => 0,
    ]));
})->throws(ValidationException::class);

it('denies users who cannot update the property owner bedroom', function () {
    $guest = makeGuest();
    $bedroom = Bedroom::factory()->create();

    app(AttachBedTypeToBedroom::class)->handle($guest, $bedroom, validBedTypeBedroomInput());
})->throws(AuthorizationException::class);
