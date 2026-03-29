<?php

use App\Models\Platform;
use Database\Seeders\PlatformSeeder;

it('creates the expected platforms', function () {
    $this->seed(PlatformSeeder::class);

    $expectedNames = ['direct', 'airbnb', 'booking', 'fincas-de-la-villa'];
    $dbNames = Platform::query()->pluck('slug')->sort()->values()->all();

    expect($dbNames)->toBe(collect($expectedNames)->sort()->values()->all())
        ->and(Platform::query()->count())->toBe(4);
});

it('is idempotent', function () {
    $this->seed(PlatformSeeder::class);
    $firstCount = Platform::query()->count();

    $this->seed(PlatformSeeder::class);
    $secondCount = Platform::query()->count();

    expect($firstCount)->toBe(4)
        ->and($secondCount)->toBe($firstCount);
});

it('does not remove extra platforms', function () {
    $custom = Platform::factory()->create(['slug' => 'custom-platform']);

    $this->seed(PlatformSeeder::class);

    expect(Platform::query()->where('slug', 'custom-platform')->exists())->toBeTrue()
        ->and(Platform::query()->count())->toBe(5);
});

it('stores commission values correctly', function () {
    $this->seed(PlatformSeeder::class);

    $airbnb = Platform::query()->where('slug', 'airbnb')->first();
    $direct = Platform::query()->where('slug', 'direct')->first();

    expect($airbnb->commission)->toBe('0.1550')
        ->and($airbnb->commission_tax)->toBe('0.1900')
        ->and($direct->commission)->toBe('0.0000')
        ->and($direct->commission_tax)->toBe('0.0000');
});
