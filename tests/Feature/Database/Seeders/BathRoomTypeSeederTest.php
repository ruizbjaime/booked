<?php

use App\Models\BathRoomType;
use Database\Seeders\BathRoomTypeSeeder;
use Database\Seeders\DatabaseSeeder;

it('creates the expected bathroom types', function () {
    $this->seed(BathRoomTypeSeeder::class);

    $expectedNames = [
        'full-bathroom',
        'half-bathroom',
    ];

    $dbNames = BathRoomType::query()->pluck('name')->sort()->values()->all();

    expect($dbNames)->toBe($expectedNames)
        ->and(BathRoomType::query()->count())->toBe(2);
});

it('includes full bathroom with expected labels description and order', function () {
    $this->seed(BathRoomTypeSeeder::class);

    $bathRoomType = BathRoomType::query()->where('name', 'full-bathroom')->first();

    expect($bathRoomType)
        ->not->toBeNull()
        ->name_en->toBe('Full Bathroom')
        ->name_es->toBe('Baño completo')
        ->description->toBe('Incluye ducha.')
        ->sort_order->toBe(1);
});

it('includes half bathroom with expected labels description and order', function () {
    $this->seed(BathRoomTypeSeeder::class);

    $bathRoomType = BathRoomType::query()->where('name', 'half-bathroom')->first();

    expect($bathRoomType)
        ->not->toBeNull()
        ->name_en->toBe('Half Bathroom')
        ->name_es->toBe('Medio baño')
        ->description->toBe('No incluye ducha.')
        ->sort_order->toBe(2);
});

it('does not remove extra bathroom types', function () {
    BathRoomType::factory()->create(['name' => 'custom-bathroom-type']);

    $this->seed(BathRoomTypeSeeder::class);

    expect(BathRoomType::query()->where('name', 'custom-bathroom-type')->exists())->toBeTrue()
        ->and(BathRoomType::query()->count())->toBe(3);
});

it('is idempotent', function () {
    $this->seed(BathRoomTypeSeeder::class);
    $firstCount = BathRoomType::query()->count();

    $this->seed(BathRoomTypeSeeder::class);
    $secondCount = BathRoomType::query()->count();

    expect($firstCount)->toBe(2)
        ->and($secondCount)->toBe($firstCount);
});

it('is executed by the database seeder', function () {
    $this->seed(DatabaseSeeder::class);

    expect(BathRoomType::query()->count())->toBe(2);
});
