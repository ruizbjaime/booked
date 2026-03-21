<?php

use App\Models\Country;
use Database\Seeders\CountrySeeder;

it('seeds countries into the database', function () {
    $this->seed(CountrySeeder::class);

    expect(Country::query()->count())->toBeGreaterThan(100);
});

it('includes colombia with sort order 1', function () {
    $this->seed(CountrySeeder::class);

    $colombia = Country::query()->where('iso_alpha2', 'CO')->first();

    expect($colombia)
        ->not->toBeNull()
        ->en_name->toBe('Colombia')
        ->phone_code->toBe('+57')
        ->sort_order->toBe(1);
});

it('is idempotent', function () {
    $this->seed(CountrySeeder::class);
    $firstCount = Country::query()->count();

    $this->seed(CountrySeeder::class);
    $secondCount = Country::query()->count();

    expect($firstCount)->toBe($secondCount);
});
