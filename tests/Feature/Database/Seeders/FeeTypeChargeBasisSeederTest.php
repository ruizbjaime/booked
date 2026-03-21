<?php

use App\Models\FeeType;
use Database\Seeders\ChargeBasisSeeder;
use Database\Seeders\FeeTypeChargeBasisSeeder;
use Database\Seeders\FeeTypeSeeder;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\seed;

it('creates the expected fee type charge basis mappings', function () {
    seed([FeeTypeSeeder::class, ChargeBasisSeeder::class, FeeTypeChargeBasisSeeder::class]);

    $petFee = FeeType::query()->with('chargeBases')->where('name', 'pet-fee')->first();
    $earlyCheckIn = FeeType::query()->with('chargeBases')->where('name', 'early-check-in-fee')->first();

    expect($petFee)->not->toBeNull()
        ->and($petFee?->chargeBases->pluck('name')->sort()->values()->all())->toBe([
            'per_pet',
            'per_pet_per_night',
            'per_stay',
        ])
        ->and($earlyCheckIn?->chargeBases->pluck('name')->sort()->values()->all())->toBe([
            'one_time',
            'per_request',
        ]);
});

it('stores default in the pivot and keeps shared metadata in charge bases', function () {
    seed([FeeTypeSeeder::class, ChargeBasisSeeder::class, FeeTypeChargeBasisSeeder::class]);

    $petFee = FeeType::query()->with('chargeBases')->where('name', 'pet-fee')->firstOrFail();
    $defaultBasis = $petFee->chargeBases->firstWhere('pivot.is_default', true);

    expect($defaultBasis)->not->toBeNull()
        ->and($defaultBasis?->name)->toBe('per_pet')
        ->and($defaultBasis?->pivot?->metadata)->toBeNull()
        ->and($defaultBasis?->metadata['requires_quantity'])->toBeTrue()
        ->and($defaultBasis?->metadata['quantity_subject'])->toBe('pet');
});

it('is idempotent', function () {
    seed([FeeTypeSeeder::class, ChargeBasisSeeder::class, FeeTypeChargeBasisSeeder::class]);
    $firstCount = DB::table('fee_type_charge_basis')->count();

    seed(FeeTypeChargeBasisSeeder::class);
    $secondCount = DB::table('fee_type_charge_basis')->count();

    expect($secondCount)->toBe($firstCount);
});
