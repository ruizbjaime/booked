<?php

use App\Models\FeeType;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\FeeTypeSeeder;

it('creates the expected fee types', function () {
    $this->seed(FeeTypeSeeder::class);

    $expectedNames = [
        'cleaning-fee',
        'extra-guest-fee',
        'pet-fee',
        'credit-card-fee',
        'early-check-in-fee',
        'late-check-out-fee',
        'parking-fee',
    ];

    $dbNames = FeeType::query()->pluck('slug')->sort()->values()->all();

    expect($dbNames)->toBe(collect($expectedNames)->sort()->values()->all())
        ->and(FeeType::query()->count())->toBe(7);
});

it('includes fee types with expected labels and order', function () {
    $this->seed(FeeTypeSeeder::class);

    $cleaningFee = FeeType::query()->where('slug', 'cleaning-fee')->first();
    $earlyCheckInFee = FeeType::query()->where('slug', 'early-check-in-fee')->first();
    $parkingFee = FeeType::query()->where('slug', 'parking-fee')->first();

    expect($cleaningFee)
        ->not->toBeNull()
        ->en_name->toBe('Cleaning Fee')
        ->es_name->toBe('Tarifa de limpieza')
        ->order->toBe(1)
        ->and($earlyCheckInFee?->en_name)->toBe('Early Check-in Fee')
        ->and($earlyCheckInFee?->es_name)->toBe('Tarifa por entrada anticipada')
        ->and($earlyCheckInFee?->order)->toBe(5)
        ->and($parkingFee?->en_name)->toBe('Parking Fee')
        ->and($parkingFee?->es_name)->toBe('Tarifa de estacionamiento')
        ->and($parkingFee?->order)->toBe(7);
});

it('is idempotent', function () {
    $this->seed(FeeTypeSeeder::class);
    $firstCount = FeeType::query()->count();

    $this->seed(FeeTypeSeeder::class);
    $secondCount = FeeType::query()->count();

    expect($firstCount)->toBe(7)
        ->and($secondCount)->toBe($firstCount);
});

it('does not remove extra fee types', function () {
    FeeType::factory()->create(['slug' => 'custom-fee-type']);

    $this->seed(FeeTypeSeeder::class);

    expect(FeeType::query()->where('slug', 'custom-fee-type')->exists())->toBeTrue()
        ->and(FeeType::query()->count())->toBe(8);
});

it('is executed by the database seeder', function () {
    $this->seed(DatabaseSeeder::class);

    expect(FeeType::query()->count())->toBe(7);
});

it('reactivates existing seeded fee types', function () {
    FeeType::factory()->create([
        'slug' => 'pet-fee',
        'is_active' => false,
    ]);

    $this->seed(FeeTypeSeeder::class);

    expect(FeeType::query()->where('slug', 'pet-fee')->value('is_active'))->toBeTrue();
});
