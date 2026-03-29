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

    $dbNames = BedType::query()->pluck('slug')->sort()->values()->all();

    expect($dbNames)->toBe(collect($expectedNames)->sort()->values()->all())
        ->and(BedType::query()->count())->toBe(17);
});

it('includes king bed with the expected labels and capacity', function () {
    $this->seed(BedTypeSeeder::class);

    $kingBed = BedType::query()->where('slug', 'king-bed')->first();

    expect($kingBed)
        ->not->toBeNull()
        ->en_name->toBe('King Bed')
        ->es_name->toBe('Cama king')
        ->bed_capacity->toBe(2)
        ->sort_order->toBe(6);
});

it('stores colombian spanish labels for common bed types', function () {
    $this->seed(BedTypeSeeder::class);

    $twinBed = BedType::query()->where('slug', 'twin-bed')->first();
    $bunkBed = BedType::query()->where('slug', 'bunk-bed')->first();
    $rollawayBed = BedType::query()->where('slug', 'rollaway-bed')->first();

    expect($twinBed?->es_name)->toBe('Cama semidoble')
        ->and($twinBed?->bed_capacity)->toBe(2)
        ->and($bunkBed?->es_name)->toBe('Camarote')
        ->and($rollawayBed?->es_name)->toBe('Cama auxiliar plegable');
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
    BedType::factory()->create(['slug' => 'custom-bed-type']);

    $this->seed(BedTypeSeeder::class);

    expect(BedType::query()->where('slug', 'custom-bed-type')->exists())->toBeTrue()
        ->and(BedType::query()->count())->toBe(18);
});

it('is executed by the database seeder', function () {
    $this->seed(DatabaseSeeder::class);

    expect(BedType::query()->count())->toBe(17);
});

it('replaces the legacy sofa bed with single and double variants', function () {
    BedType::factory()->create([
        'slug' => 'sofa-bed',
        'en_name' => 'Sofa Bed',
        'es_name' => 'Sofa cama',
        'bed_capacity' => 2,
    ]);

    $this->seed(BedTypeSeeder::class);

    $sofaBedSingle = BedType::query()->where('slug', 'sofa-bed-single')->first();
    $sofaBedDouble = BedType::query()->where('slug', 'sofa-bed-double')->first();

    expect(BedType::query()->where('slug', 'sofa-bed')->exists())->toBeFalse()
        ->and($sofaBedSingle?->es_name)->toBe('Sofá cama sencillo')
        ->and($sofaBedSingle?->bed_capacity)->toBe(1)
        ->and($sofaBedDouble?->es_name)->toBe('Sofá cama doble')
        ->and($sofaBedDouble?->bed_capacity)->toBe(2);
});
