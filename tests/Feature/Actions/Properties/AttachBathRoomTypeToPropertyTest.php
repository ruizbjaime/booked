<?php

use App\Actions\Properties\AttachBathRoomTypeToProperty;
use App\Models\BathRoomType;
use App\Models\Property;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

function validBathRoomTypePropertyInput(array $overrides = []): array
{
    return array_merge([
        'bath_room_type_id' => BathRoomType::factory()->create()->id,
        'quantity' => 2,
    ], $overrides);
}

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('creates the property shared bathroom type association with quantity', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();

    app(AttachBathRoomTypeToProperty::class)->handle($host, $property, validBathRoomTypePropertyInput());

    expect($property->fresh()->bathRoomTypes)->toHaveCount(1)
        ->and($property->fresh()->bathRoomTypes->first()?->pivot->quantity)->toBe(2);
});

it('updates quantity when the shared bathroom type is already attached', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();
    $bathRoomType = BathRoomType::factory()->create();

    $property->bathRoomTypes()->attach($bathRoomType->id, ['quantity' => 1]);

    app(AttachBathRoomTypeToProperty::class)->handle($host, $property, [
        'bath_room_type_id' => $bathRoomType->id,
        'quantity' => 3,
    ]);

    expect($property->fresh()->bathRoomTypes)->toHaveCount(1)
        ->and($property->fresh()->bathRoomTypes->first()?->pivot->quantity)->toBe(3);
});

it('rejects quantity lower than one for shared bathroom types', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();

    app(AttachBathRoomTypeToProperty::class)->handle($host, $property, validBathRoomTypePropertyInput([
        'quantity' => 0,
    ]));
})->throws(ValidationException::class);

it('denies users who cannot update the property shared bathroom types', function () {
    $guest = makeGuest();
    $property = Property::factory()->create();

    app(AttachBathRoomTypeToProperty::class)->handle($guest, $property, validBathRoomTypePropertyInput());
})->throws(AuthorizationException::class);
