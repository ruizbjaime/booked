<?php

use App\Models\BedType;
use Database\Seeders\BedTypeSeeder;
use Database\Seeders\DatabaseSeeder;

it('creates the expected bed types', function () {
    $this->seed(BedTypeSeeder::class);

    $expectedNames = [
        'single-bed',
        'twin-bed',
        'double-bed',
        'full-bed',
        'queen-bed',
        'king-bed',
        'california-king-bed',
        'sofa-bed-single',
        'sofa-bed-double',
        'bunk-bed',
        'trundle-bed',
        'daybed',
        'murphy-bed',
        'futon',
        'crib',
        'rollaway-bed',
        'cot',
    ];

    $dbNames = BedType::query()->pluck('name')->sort()->values()->all();

    expect($dbNames)->toBe(collect($expectedNames)->sort()->values()->all())
        ->and(BedType::query()->count())->toBe(17);
});

it('includes king bed with the expected labels and capacity', function () {
    $this->seed(BedTypeSeeder::class);

    $kingBed = BedType::query()->where('name', 'king-bed')->first();

    expect($kingBed)
        ->not->toBeNull()
        ->name_en->toBe('King Bed')
        ->name_es->toBe('Cama king')
        ->bed_capacity->toBe(2)
        ->sort_order->toBe(6);
});

it('stores colombian spanish labels for common bed types', function () {
    $this->seed(BedTypeSeeder::class);

    $twinBed = BedType::query()->where('name', 'twin-bed')->first();
    $bunkBed = BedType::query()->where('name', 'bunk-bed')->first();
    $rollawayBed = BedType::query()->where('name', 'rollaway-bed')->first();

    expect($twinBed?->name_es)->toBe('Cama semidoble')
        ->and($twinBed?->bed_capacity)->toBe(2)
        ->and($bunkBed?->name_es)->toBe('Camarote')
        ->and($rollawayBed?->name_es)->toBe('Cama auxiliar plegable');
});

it('is idempotent', function () {
    $this->seed(BedTypeSeeder::class);
    $firstCount = BedType::query()->count();

    $this->seed(BedTypeSeeder::class);
    $secondCount = BedType::query()->count();

    expect($firstCount)->toBe(17)
        ->and($secondCount)->toBe($firstCount);
});

it('does not remove extra bed types', function () {
    BedType::factory()->create(['name' => 'custom-bed-type']);

    $this->seed(BedTypeSeeder::class);

    expect(BedType::query()->where('name', 'custom-bed-type')->exists())->toBeTrue()
        ->and(BedType::query()->count())->toBe(18);
});

it('is executed by the database seeder', function () {
    $this->seed(DatabaseSeeder::class);

    expect(BedType::query()->count())->toBe(17);
});

it('replaces the legacy sofa bed with single and double variants', function () {
    BedType::factory()->create([
        'name' => 'sofa-bed',
        'name_en' => 'Sofa Bed',
        'name_es' => 'Sofa cama',
        'bed_capacity' => 2,
    ]);

    $this->seed(BedTypeSeeder::class);

    expect(BedType::query()->where('name', 'sofa-bed')->exists())->toBeFalse()
        ->and(BedType::query()->where('name', 'sofa-bed-single')->first()?->name_es)->toBe('Sofá cama sencillo')
        ->and(BedType::query()->where('name', 'sofa-bed-single')->first()?->bed_capacity)->toBe(1)
        ->and(BedType::query()->where('name', 'sofa-bed-double')->first()?->name_es)->toBe('Sofá cama doble')
        ->and(BedType::query()->where('name', 'sofa-bed-double')->first()?->bed_capacity)->toBe(2);
});
