<?php

use App\Actions\Bedrooms\AttachBathRoomTypeToBedroom;
use App\Models\BathRoomType;
use App\Models\Bedroom;
use App\Models\Property;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

function validBathRoomTypeBedroomInput(array $overrides = []): array
{
    return array_merge([
        'bath_room_type_id' => BathRoomType::factory()->create()->id,
        'quantity' => 2,
    ], $overrides);
}

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('creates the bedroom bathroom type association with quantity', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();
    $bedroom = Bedroom::factory()->create(['property_id' => $property->id]);

    $input = validBathRoomTypeBedroomInput();

    app(AttachBathRoomTypeToBedroom::class)->handle($host, $bedroom, $input);

    expect($bedroom->fresh()->bathRoomTypes)->toHaveCount(1)
        ->and($bedroom->fresh()->bathRoomTypes->first()?->pivot->quantity)->toBe(2);
});

it('updates quantity when the bathroom type is already attached', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();
    $bedroom = Bedroom::factory()->create(['property_id' => $property->id]);
    $bathRoomType = BathRoomType::factory()->create();

    $bedroom->bathRoomTypes()->attach($bathRoomType->id, ['quantity' => 1]);

    app(AttachBathRoomTypeToBedroom::class)->handle($host, $bedroom, [
        'bath_room_type_id' => $bathRoomType->id,
        'quantity' => 3,
    ]);

    expect($bedroom->fresh()->bathRoomTypes)->toHaveCount(1)
        ->and($bedroom->fresh()->bathRoomTypes->first()?->pivot->quantity)->toBe(3);
});

it('rejects quantity lower than one', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();
    $bedroom = Bedroom::factory()->create(['property_id' => $property->id]);

    app(AttachBathRoomTypeToBedroom::class)->handle($host, $bedroom, validBathRoomTypeBedroomInput([
        'quantity' => 0,
    ]));
})->throws(ValidationException::class);

it('denies users who cannot update the property owner bedroom', function () {
    $guest = makeGuest();
    $bedroom = Bedroom::factory()->create();

    app(AttachBathRoomTypeToBedroom::class)->handle($guest, $bedroom, validBathRoomTypeBedroomInput());
})->throws(AuthorizationException::class);
