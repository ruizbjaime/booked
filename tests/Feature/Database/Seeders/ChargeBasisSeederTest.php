<?php

use App\Models\ChargeBasis;
use Database\Seeders\ChargeBasisSeeder;
use Database\Seeders\DatabaseSeeder;

it('creates the expected charge bases', function () {
    $this->seed(ChargeBasisSeeder::class);

    $expectedNames = [
        'one_time',
        'per_stay',
        'per_night',
        'per_request',
        'per_use',
        'per_guest',
        'per_guest_per_night',
        'per_pet',
        'per_pet_per_night',
        'per_vehicle',
    ];

    $dbNames = ChargeBasis::query()->pluck('name')->sort()->values()->all();

    expect($dbNames)->toBe(collect($expectedNames)->sort()->values()->all())
        ->and(ChargeBasis::query()->count())->toBe(10);
});

it('includes common charge bases with expected labels and metadata support', function () {
    $this->seed(ChargeBasisSeeder::class);

    $perStay = ChargeBasis::query()->where('name', 'per_stay')->first();
    $perPetPerNight = ChargeBasis::query()->where('name', 'per_pet_per_night')->first();

    expect($perStay)
        ->not->toBeNull()
        ->en_name->toBe('Per Stay')
        ->es_name->toBe('Por estadía')
        ->is_active->toBeTrue()
        ->and($perStay?->metadata['requires_quantity'])->toBeFalse()
        ->and($perPetPerNight?->en_name)->toBe('Per Pet Per Night')
        ->and($perPetPerNight?->es_name)->toBe('Por mascota por noche')
        ->and($perPetPerNight?->metadata['requires_quantity'])->toBeTrue()
        ->and($perPetPerNight?->metadata['quantity_subject'])->toBe('pet');
});

it('is idempotent', function () {
    $this->seed(ChargeBasisSeeder::class);
    $firstCount = ChargeBasis::query()->count();

    $this->seed(ChargeBasisSeeder::class);
    $secondCount = ChargeBasis::query()->count();

    expect($firstCount)->toBe(10)
        ->and($secondCount)->toBe($firstCount);
});

it('is executed by the database seeder', function () {
    $this->seed(DatabaseSeeder::class);

    expect(ChargeBasis::query()->count())->toBe(10);
});
