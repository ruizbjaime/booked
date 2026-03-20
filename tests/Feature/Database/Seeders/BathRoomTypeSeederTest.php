<?php

use App\Models\BathRoomType;
use Database\Seeders\BathRoomTypeSeeder;
use Database\Seeders\DatabaseSeeder;

use function Pest\Laravel\seed;

it('creates the expected bathroom types', function () {
    seed(BathRoomTypeSeeder::class);

    $expectedNames = [
        'full-bathroom',
        'half-bathroom',
    ];

    $dbNames = BathRoomType::query()->pluck('name')->sort()->values()->all();

    expect($dbNames)->toBe($expectedNames)
        ->and(BathRoomType::query()->count())->toBe(2);
});

it('includes full bathroom with expected labels description and order', function () {
    seed(BathRoomTypeSeeder::class);

    $bathRoomType = BathRoomType::query()->where('name', 'full-bathroom')->first();

    expect($bathRoomType)
        ->not->toBeNull()
        ->name_en->toBe('Full Bathroom')
        ->name_es->toBe('Baño completo')
        ->description->toBe('Incluye ducha.')
        ->sort_order->toBe(1);
});

it('includes half bathroom with expected labels description and order', function () {
    seed(BathRoomTypeSeeder::class);

    $bathRoomType = BathRoomType::query()->where('name', 'half-bathroom')->first();

    expect($bathRoomType)
        ->not->toBeNull()
        ->name_en->toBe('Half Bathroom')
        ->name_es->toBe('Medio baño')
        ->description->toBe('No incluye ducha.')
        ->sort_order->toBe(2);
});

it('is idempotent', function () {
    seed(BathRoomTypeSeeder::class);
    $firstCount = BathRoomType::query()->count();

    seed(BathRoomTypeSeeder::class);
    $secondCount = BathRoomType::query()->count();

    expect($firstCount)->toBe(2)
        ->and($secondCount)->toBe($firstCount);
});

it('is executed by the database seeder', function () {
    seed(DatabaseSeeder::class);

    expect(BathRoomType::query()->count())->toBe(2);
});
