<?php

use App\Actions\FeeTypes\UpdateFeeTypeChargeBases;
use App\Models\ChargeBasis;
use App\Models\FeeType;
use App\Models\FeeTypeChargeBasis;
use Database\Seeders\ChargeBasisSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
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

test('non-admin user is denied from syncing charge bases', function () {
    $guest = makeGuest();
    $feeType = FeeType::factory()->create();
    $perStay = ChargeBasis::query()->where('name', 'per_stay')->firstOrFail();

    app(UpdateFeeTypeChargeBases::class)->handle($guest, $feeType, [
        ['charge_basis_id' => $perStay->id, 'is_active' => true],
    ]);
})->throws(AuthorizationException::class);

test('validates non-existent charge basis id', function () {
    $admin = makeAdmin();
    $feeType = FeeType::factory()->create();

    app(UpdateFeeTypeChargeBases::class)->handle($admin, $feeType, [
        ['charge_basis_id' => 99999, 'is_active' => true],
    ]);
})->throws(ValidationException::class);

test('preserves existing pivot data on re-sync', function () {
    $admin = makeAdmin();
    $feeType = FeeType::factory()->create();

    $perStay = ChargeBasis::query()->where('name', 'per_stay')->firstOrFail();
    $perNight = ChargeBasis::query()->where('name', 'per_night')->firstOrFail();

    app(UpdateFeeTypeChargeBases::class)->handle($admin, $feeType, [
        ['charge_basis_id' => $perStay->id, 'is_active' => true],
    ]);

    FeeTypeChargeBasis::query()
        ->where('fee_type_id', $feeType->id)
        ->where('charge_basis_id', $perStay->id)
        ->update([
            'is_default' => true,
            'sort_order' => 5,
            'metadata' => json_encode(['test' => true]),
        ]);

    app(UpdateFeeTypeChargeBases::class)->handle($admin, $feeType->fresh(), [
        ['charge_basis_id' => $perStay->id, 'is_active' => true],
        ['charge_basis_id' => $perNight->id, 'is_active' => true],
    ]);

    $pivot = FeeTypeChargeBasis::query()
        ->where('fee_type_id', $feeType->id)
        ->where('charge_basis_id', $perStay->id)
        ->firstOrFail();

    expect($pivot->is_default)->toBeTrue()
        ->and($pivot->sort_order)->toBe(5)
        ->and($pivot->metadata)->toBe(['test' => true]);
});
