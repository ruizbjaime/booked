<?php

use App\Models\BathRoomType;
use App\Models\BathRoomTypeProperty;
use App\Models\Property;

it('belongs to a property', function () {
    $property = Property::factory()->create();
    $bathRoomType = BathRoomType::factory()->create();

    $property->bathRoomTypes()->attach($bathRoomType->id, ['quantity' => 1]);

    $pivot = BathRoomTypeProperty::query()->first();

    expect($pivot->property)
        ->not->toBeNull()
        ->id->toBe($property->id);
});

it('belongs to a bathroom type', function () {
    $property = Property::factory()->create();
    $bathRoomType = BathRoomType::factory()->create();

    $property->bathRoomTypes()->attach($bathRoomType->id, ['quantity' => 2]);

    $pivot = BathRoomTypeProperty::query()->first();

    expect($pivot->bathRoomType)
        ->not->toBeNull()
        ->id->toBe($bathRoomType->id);
});
