<?php

use App\Models\FeeType;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\FeeTypeSeeder;

it('creates the expected fee types', function () {
    $this->seed(FeeTypeSeeder::class);

    $expectedNames = [
        'cleaning-fee',
        'short-stay-cleaning-fee',
        'pet-fee',
        'extra-guest-fee',
        'resort-fee',
        'linen-fee',
        'towel-fee',
        'management-fee',
        'community-fee',
        'service-charge',
        'destination-charge',
        'destination-tax',
        'tourism-fee',
        'city-tax',
        'municipality-fee',
        'government-tax',
        'vat-sales-tax',
        'environment-fee',
        'sustainability-fee',
        'heritage-tax',
        'local-conservation-fee',
        'city-ticket-fee',
        'hot-spring-tax',
        'spa-tax',
        'parking-fee',
        'internet-wifi-fee',
        'credit-card-fee',
        'smoking-fee',
        'early-check-in-fee',
        'late-check-out-fee',
        'facility-usage-fee',
    ];

    $dbNames = FeeType::query()->pluck('name')->sort()->values()->all();

    expect($dbNames)->toBe(collect($expectedNames)->sort()->values()->all())
        ->and(FeeType::query()->count())->toBe(31);
});

it('includes common ota fee types with expected labels and order', function () {
    $this->seed(FeeTypeSeeder::class);

    $cleaningFee = FeeType::query()->where('name', 'cleaning-fee')->first();
    $cityTax = FeeType::query()->where('name', 'city-tax')->first();
    $earlyCheckInFee = FeeType::query()->where('name', 'early-check-in-fee')->first();
    $parkingFee = FeeType::query()->where('name', 'parking-fee')->first();

    expect($cleaningFee)
        ->not->toBeNull()
        ->en_name->toBe('Cleaning Fee')
        ->es_name->toBe('Tarifa de limpieza')
        ->order->toBe(1)
        ->and($cityTax?->en_name)->toBe('City Tax')
        ->and($cityTax?->es_name)->toBe('Impuesto municipal')
        ->and($cityTax?->order)->toBe(14)
        ->and($earlyCheckInFee?->en_name)->toBe('Early Check-in Fee')
        ->and($earlyCheckInFee?->es_name)->toBe('Tarifa por entrada anticipada')
        ->and($earlyCheckInFee?->order)->toBe(29)
        ->and($parkingFee?->en_name)->toBe('Parking Fee')
        ->and($parkingFee?->es_name)->toBe('Tarifa de estacionamiento')
        ->and($parkingFee?->order)->toBe(25);
});

it('is idempotent', function () {
    $this->seed(FeeTypeSeeder::class);
    $firstCount = FeeType::query()->count();

    $this->seed(FeeTypeSeeder::class);
    $secondCount = FeeType::query()->count();

    expect($firstCount)->toBe(31)
        ->and($secondCount)->toBe($firstCount);
});

it('does not remove extra fee types', function () {
    FeeType::factory()->create(['name' => 'custom-fee-type']);

    $this->seed(FeeTypeSeeder::class);

    expect(FeeType::query()->where('name', 'custom-fee-type')->exists())->toBeTrue()
        ->and(FeeType::query()->count())->toBe(32);
});

it('is executed by the database seeder', function () {
    $this->seed(DatabaseSeeder::class);

    expect(FeeType::query()->count())->toBe(31);
});
