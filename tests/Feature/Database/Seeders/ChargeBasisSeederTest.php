<?php

use App\Models\ChargeBasis;
use Database\Seeders\ChargeBasisSeeder;
use Database\Seeders\DatabaseSeeder;

it('creates the expected charge bases', function () {
    $this->seed(ChargeBasisSeeder::class);

    $expectedNames = [
        'per-stay',
        'per-night',
        'per-request',
        'per-use',
        'per-guest',
        'per-guest-per-night',
        'per-pet',
        'per-pet-per-night',
        'per-vehicle',
    ];

    $dbNames = ChargeBasis::query()->pluck('slug')->sort()->values()->all();

    expect($dbNames)->toBe(collect($expectedNames)->sort()->values()->all())
        ->and(ChargeBasis::query()->count())->toBe(9);
});

it('includes common charge bases with expected labels and metadata support', function () {
    $this->seed(ChargeBasisSeeder::class);

    $perStay = ChargeBasis::query()->where('slug', 'per-stay')->first();
    $perPetPerNight = ChargeBasis::query()->where('slug', 'per-pet-per-night')->first();

    expect($perStay)
        ->not->toBeNull()
        ->en_name->toBe('Per Stay')
        ->es_name->toBe('Por estadía')
        ->en_description->toBe('Applied once for the full stay.')
        ->es_description->toBe('Se aplica una vez por toda la estadía.')
        ->is_active->toBeTrue()
        ->and($perStay?->metadata['requires_quantity'])->toBeFalse()
        ->and($perPetPerNight?->en_name)->toBe('Per Pet Per Night')
        ->and($perPetPerNight?->es_name)->toBe('Por mascota por noche')
        ->and($perPetPerNight?->en_description)->toBe('Applied for each pet and each night.')
        ->and($perPetPerNight?->es_description)->toBe('Se aplica por cada mascota y cada noche.')
        ->and($perPetPerNight?->metadata['requires_quantity'])->toBeTrue()
        ->and($perPetPerNight?->metadata['quantity_subject'])->toBe('pet');
});

it('is idempotent', function () {
    $this->seed(ChargeBasisSeeder::class);
    $firstCount = ChargeBasis::query()->count();

    $this->seed(ChargeBasisSeeder::class);
    $secondCount = ChargeBasis::query()->count();

    expect($firstCount)->toBe(9)
        ->and($secondCount)->toBe($firstCount);
});

it('is executed by the database seeder', function () {
    $this->seed(DatabaseSeeder::class);

    expect(ChargeBasis::query()->count())->toBe(9);
});
