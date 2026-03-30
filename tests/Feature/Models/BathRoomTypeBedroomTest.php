<?php

use App\Models\BathRoomType;
use App\Models\BathRoomTypeBedroom;
use App\Models\Bedroom;

it('belongs to a bedroom', function () {
    $bedroom = Bedroom::factory()->create();
    $bathRoomType = BathRoomType::factory()->create();

    $bedroom->bathRoomTypes()->attach($bathRoomType->id, ['quantity' => 1]);

    $pivot = BathRoomTypeBedroom::query()->first();

    expect($pivot->bedroom)
        ->not->toBeNull()
        ->id->toBe($bedroom->id);
});

it('belongs to a bathroom type', function () {
    $bedroom = Bedroom::factory()->create();
    $bathRoomType = BathRoomType::factory()->create();

    $bedroom->bathRoomTypes()->attach($bathRoomType->id, ['quantity' => 2]);

    $pivot = BathRoomTypeBedroom::query()->first();

    expect($pivot->bathRoomType)
        ->not->toBeNull()
        ->id->toBe($bathRoomType->id);
});
