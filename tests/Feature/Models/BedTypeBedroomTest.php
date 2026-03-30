<?php

use App\Models\BedTypeBedroom;
use App\Models\Bedroom;
use App\Models\BedType;

it('belongs to a bedroom', function () {
    $bedroom = Bedroom::factory()->create();
    $bedType = BedType::factory()->create();

    $bedroom->bedTypes()->attach($bedType->id, ['quantity' => 1]);

    $pivot = BedTypeBedroom::query()->first();

    expect($pivot->bedroom)
        ->not->toBeNull()
        ->id->toBe($bedroom->id);
});

it('belongs to a bed type', function () {
    $bedroom = Bedroom::factory()->create();
    $bedType = BedType::factory()->create();

    $bedroom->bedTypes()->attach($bedType->id, ['quantity' => 2]);

    $pivot = BedTypeBedroom::query()->first();

    expect($pivot->bedType)
        ->not->toBeNull()
        ->id->toBe($bedType->id);
});
