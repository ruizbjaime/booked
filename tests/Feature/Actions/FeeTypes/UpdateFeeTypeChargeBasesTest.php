<?php

use App\Actions\FeeTypes\UpdateFeeTypeChargeBases;
use App\Models\ChargeBasis;
use App\Models\FeeType;
use Database\Seeders\ChargeBasisSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, ChargeBasisSeeder::class]);
});

test('syncs selected charge bases preserving pivot compatibility fields', function () {
    $admin = makeAdmin();
    $feeType = FeeType::factory()->create();

    $perStay = ChargeBasis::query()->where('name', 'per_stay')->firstOrFail();
    $perNight = ChargeBasis::query()->where('name', 'per_night')->firstOrFail();

    app(UpdateFeeTypeChargeBases::class)->handle($admin, $feeType, [
        ['charge_basis_id' => $perStay->id, 'is_active' => true],
        ['charge_basis_id' => $perNight->id, 'is_active' => true],
    ]);

    $feeType->refresh()->load('chargeBases');

    expect($feeType->chargeBases)->toHaveCount(2)
        ->and($feeType->chargeBases->pluck('id')->sort()->values()->all())->toBe([$perStay->id, $perNight->id])
        ->and($feeType->chargeBases->firstWhere('id', $perStay->id)?->pivot?->sort_order)->toBe($perStay->order);
});

test('removes deselected charge bases on sync', function () {
    $admin = makeAdmin();
    $feeType = FeeType::factory()->create();

    $perStay = ChargeBasis::query()->where('name', 'per_stay')->firstOrFail();
    $perNight = ChargeBasis::query()->where('name', 'per_night')->firstOrFail();

    app(UpdateFeeTypeChargeBases::class)->handle($admin, $feeType, [
        ['charge_basis_id' => $perStay->id, 'is_active' => true],
        ['charge_basis_id' => $perNight->id, 'is_active' => true],
    ]);

    app(UpdateFeeTypeChargeBases::class)->handle($admin, $feeType->fresh(), [
        ['charge_basis_id' => $perNight->id, 'is_active' => true],
    ]);

    expect($feeType->fresh()->chargeBases()->pluck('charge_bases.id')->all())->toBe([$perNight->id]);
});

test('rejects duplicate charge bases', function () {
    $admin = makeAdmin();
    $feeType = FeeType::factory()->create();
    $perStay = ChargeBasis::query()->where('name', 'per_stay')->firstOrFail();

    app(UpdateFeeTypeChargeBases::class)->handle($admin, $feeType, [
        ['charge_basis_id' => $perStay->id, 'is_active' => true],
        ['charge_basis_id' => $perStay->id, 'is_active' => true],
    ]);
})->throws(ValidationException::class);
