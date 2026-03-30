<?php

use App\Actions\Bedrooms\AttachBathRoomTypeToBedroom;
use App\Actions\Bedrooms\AttachBedTypeToBedroom;
use App\Actions\Bedrooms\CreateBedroom;
use App\Actions\Bedrooms\DetachBathRoomTypeFromBedroom;
use App\Actions\Bedrooms\DetachBedTypeFromBedroom;
use App\Actions\Properties\AttachBathRoomTypeToProperty;
use App\Actions\Properties\DetachBathRoomTypeFromProperty;
use App\Models\BathRoomType;
use App\Models\Bedroom;
use App\Models\BedType;
use App\Models\Property;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->host = makeHost();
});

test('total_bedrooms counts bedrooms for the property', function () {
    $property = Property::factory()->create();

    expect($property->total_bedrooms)->toBe(0);

    Bedroom::factory()->create(['property_id' => $property->id]);
    Bedroom::factory()->create(['property_id' => $property->id]);
    $property->flushAccommodationTotals();

    expect($property->total_bedrooms)->toBe(2);
});

test('cache is invalidated when a bedroom is created via action', function () {
    $property = Property::factory()->forUser($this->host)->create();

    expect($property->total_bedrooms)->toBe(0);

    app(CreateBedroom::class)->handle($this->host, $property, [
        'en_name' => 'Main Bedroom',
        'es_name' => 'Habitación principal',
    ]);

    expect($property->total_bedrooms)->toBe(1);
});

test('total_beds sums bed quantities across all bedrooms', function () {
    $property = Property::factory()->create();

    $bedroom1 = Bedroom::factory()->create(['property_id' => $property->id]);
    $bedroom2 = Bedroom::factory()->create(['property_id' => $property->id]);

    $queenBed = BedType::factory()->create();
    $singleBed = BedType::factory()->create();

    $bedroom1->bedTypes()->attach($queenBed->id, ['quantity' => 2]);
    $bedroom1->bedTypes()->attach($singleBed->id, ['quantity' => 1]);
    $bedroom2->bedTypes()->attach($queenBed->id, ['quantity' => 1]);

    expect($property->total_beds)->toBe(4);
});

test('total_beds returns zero when no bedrooms exist', function () {
    $property = Property::factory()->create();

    expect($property->total_beds)->toBe(0);
});

test('total_bathrooms sums bedroom and shared bathroom quantities', function () {
    $property = Property::factory()->create();

    $bedroom = Bedroom::factory()->create(['property_id' => $property->id]);

    $privateBath = BathRoomType::factory()->create();
    $sharedBath = BathRoomType::factory()->create();

    $bedroom->bathRoomTypes()->attach($privateBath->id, ['quantity' => 1]);
    $property->bathRoomTypes()->attach($sharedBath->id, ['quantity' => 2]);

    expect($property->total_bathrooms)->toBe(3);
});

test('total_bathrooms returns zero when no bathrooms exist', function () {
    $property = Property::factory()->create();

    expect($property->total_bathrooms)->toBe(0);
});

test('total_bathrooms counts only shared bathrooms when bedrooms have none', function () {
    $property = Property::factory()->create();
    Bedroom::factory()->create(['property_id' => $property->id]);

    $sharedBath = BathRoomType::factory()->create();
    $property->bathRoomTypes()->attach($sharedBath->id, ['quantity' => 3]);

    expect($property->total_bathrooms)->toBe(3);
});

test('totals exclude beds and bathrooms from other properties', function () {
    $property = Property::factory()->create();
    $otherProperty = Property::factory()->create();

    $bedroom = Bedroom::factory()->create(['property_id' => $property->id]);
    $otherBedroom = Bedroom::factory()->create(['property_id' => $otherProperty->id]);

    $bedType = BedType::factory()->create();
    $bathType = BathRoomType::factory()->create();

    $bedroom->bedTypes()->attach($bedType->id, ['quantity' => 1]);
    $otherBedroom->bedTypes()->attach($bedType->id, ['quantity' => 5]);

    $bedroom->bathRoomTypes()->attach($bathType->id, ['quantity' => 1]);
    $otherBedroom->bathRoomTypes()->attach($bathType->id, ['quantity' => 4]);

    $property->bathRoomTypes()->attach($bathType->id, ['quantity' => 2]);
    $otherProperty->bathRoomTypes()->attach($bathType->id, ['quantity' => 7]);

    expect($property->total_beds)->toBe(1)
        ->and($property->total_bathrooms)->toBe(3);
});

test('cache is invalidated when a bed type is attached via action', function () {
    $property = Property::factory()->forUser($this->host)->create();
    $bedroom = Bedroom::factory()->create(['property_id' => $property->id]);
    $bedType = BedType::factory()->create(['is_active' => true]);

    expect($property->total_beds)->toBe(0);

    app(AttachBedTypeToBedroom::class)->handle($this->host, $bedroom, [
        'bed_type_id' => $bedType->id,
        'quantity' => 3,
    ]);

    expect($property->total_beds)->toBe(3);
});

test('cache is invalidated when a bed type is detached via action', function () {
    $property = Property::factory()->forUser($this->host)->create();
    $bedroom = Bedroom::factory()->create(['property_id' => $property->id]);
    $bedType = BedType::factory()->create(['is_active' => true]);
    $bedroom->bedTypes()->attach($bedType->id, ['quantity' => 2]);

    expect($property->total_beds)->toBe(2);

    app(DetachBedTypeFromBedroom::class)->handle($this->host, $bedroom, $bedType);

    expect($property->total_beds)->toBe(0);
});

test('cache is invalidated when a bathroom type is attached to bedroom via action', function () {
    $property = Property::factory()->forUser($this->host)->create();
    $bedroom = Bedroom::factory()->create(['property_id' => $property->id]);
    $bathType = BathRoomType::factory()->create();

    expect($property->total_bathrooms)->toBe(0);

    app(AttachBathRoomTypeToBedroom::class)->handle($this->host, $bedroom, [
        'bath_room_type_id' => $bathType->id,
        'quantity' => 2,
    ]);

    expect($property->total_bathrooms)->toBe(2);
});

test('cache is invalidated when a bathroom type is detached from bedroom via action', function () {
    $property = Property::factory()->forUser($this->host)->create();
    $bedroom = Bedroom::factory()->create(['property_id' => $property->id]);
    $bathType = BathRoomType::factory()->create();
    $bedroom->bathRoomTypes()->attach($bathType->id, ['quantity' => 1]);

    expect($property->total_bathrooms)->toBe(1);

    app(DetachBathRoomTypeFromBedroom::class)->handle($this->host, $bedroom, $bathType);

    expect($property->total_bathrooms)->toBe(0);
});

test('cache is invalidated when a shared bathroom type is attached via action', function () {
    $property = Property::factory()->forUser($this->host)->create();
    $bathType = BathRoomType::factory()->create();

    expect($property->total_bathrooms)->toBe(0);

    app(AttachBathRoomTypeToProperty::class)->handle($this->host, $property, [
        'bath_room_type_id' => $bathType->id,
        'quantity' => 4,
    ]);

    expect($property->total_bathrooms)->toBe(4);
});

test('cache is invalidated when a shared bathroom type is detached via action', function () {
    $property = Property::factory()->forUser($this->host)->create();
    $bathType = BathRoomType::factory()->create();
    $property->bathRoomTypes()->attach($bathType->id, ['quantity' => 2]);

    expect($property->total_bathrooms)->toBe(2);

    app(DetachBathRoomTypeFromProperty::class)->handle($this->host, $property, $bathType);

    expect($property->total_bathrooms)->toBe(0);
});
